<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DamageItemRequest;
use App\Http\Resources\DamageItemResource;
use App\Models\DamageItem;
use App\Models\Stock;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DamageItemController extends Controller
{

    const DATE = 'date';
    const STOCK_ID = 'stock_id';
    const QUANTITY = 'quantity';
    const IS_SALE = 'is_sale';

    private $model;

    public function __construct(DamageItem $model)
    {
        $this->model = $model;
    }

    public function index()
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;

        if ($shop_id == 0) {
            $data = $this->model::OrderBy('created_at', 'desc')->get();
            return response()->json(["status" => "success", "data" => DamageItemResource::collection($data), "total" => count($data)]);
        }

        $data = $this->model::where('shop_id', '=', $shop_id)->orderBy('created_at', 'desc')->get();
        return response()->json(["status" => "success", "data" => DamageItemResource::collection($data), "total" => count($data)]);
    }

    public function store(DamageItemRequest $request)
    {
        DB::beginTransaction();
        try {
            $date = trim($request->get(self::DATE));
            $stock_id = trim($request->get(self::STOCK_ID));
            $quantity = trim($request->get(self::QUANTITY));
            $is_sale = trim($request->get(self::IS_SALE));

            $user = Auth::user();
            $shop_id = $user->shop_id;

            $model = new $this->model;
            $model->date = $date;
            $model->stock_id = $stock_id;
            $model->quantity = $quantity;
            $model->is_sale = $is_sale;
            $model->shop_id = $shop_id;

            $model->save();

            if (!$is_sale) {
                $stock = Stock::find($stock_id);
                if ($stock->quantity > $quantity) {

                    $stock->quantity -= $quantity;
                } else {
                    return jsend_fail(['message' => 'Quantity is greater than stock.'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $stock->save();
            }

            DB::commit();
            return jsend_success(new DamageItemResource($model), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(__('api.saved-failed', ['model' => class_basename($this->model)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(__('api.saved-failed', ['model' => class_basename($this->model)]), [
                $ex->getCode(),
                ErrorType::SAVE_ERROR,
            ]);
        }
    }

    public function show(DamageItem $damage_item)
    {
        return jsend_success(new DamageItemResource($damage_item));
    }

    public function update(DamageItemRequest $request, DamageItem $damage_item)
    {
        DB::beginTransaction();
        try {
            $date = trim($request->get(self::DATE));
            $stock_id = trim($request->get(self::STOCK_ID));
            $quantity = trim($request->get(self::QUANTITY));
            $is_sale = trim($request->get(self::IS_SALE));

            $user = Auth::user();
            $shop_id = $user->shop_id;

            if (!$is_sale) {
                $stock = Stock::find($stock_id);
                if ($damage_item->quantity > $quantity) {
                    $stock->quantity += $damage_item->quantity - $quantity;
                    $stock->save();
                } else {
                    if ($stock->quantity > $quantity) {

                        $stock->quantity -= $quantity - $damage_item->quantity;
                    } else {
                        return jsend_fail(['message' => 'Quantity is greater than stock.'], JsonResponse::HTTP_BAD_REQUEST);
                    }

                    $stock->save();
                }
            }

            $damage_item->date = $date;
            $damage_item->stock_id = $stock_id;
            $damage_item->quantity = $quantity;
            $damage_item->is_sale = $is_sale;
            $damage_item->shop_id = $shop_id;
            $damage_item->save();

            DB::commit();
            return jsend_success(new DamageItemResource($damage_item), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(__('api.saved-failed', ['model' => class_basename($this->model)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(__('api.saved-failed', ['model' => class_basename($this->model)]), [
                $ex->getCode(),
                ErrorType::SAVE_ERROR,
            ]);
        }
    }

    public function destroy(DamageItem $damage_item)
    {

        DB::beginTransaction();
        try {
            if (!$damage_item->is_sale) {
                $stock = Stock::find($damage_item->stock_id);
                $stock->quantity += $damage_item->quantity;
                $stock->save();
            }
            $damage_item->delete();

            DB::commit();
            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    public function batchInsert()
    {
        request()->validate([
            'damage_items' => 'required',
        ]);

        $data = request('damage_items');

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $shop_id = $user->shop_id;

            $saved_data = [];

            foreach ($data as $index => $d) {
                $damage_item = new $this->model;
                $damage_item->date = trim($d["date"]);
                $damage_item->stock_id = $d["stock_id"];
                $damage_item->quantity = $d['quantity'];
                $damage_item->is_sale = $d['is_sale'];
                $damage_item->shop_id = $shop_id;
                $damage_item->save();

                if (!$damage_item->is_sale) {
                    $stock = Stock::find($damage_item->stock_id);
                    if ($stock->quantity >= $damage_item->quantity) {

                        $stock->quantity -= $damage_item->quantity;
                    } else {
                        return jsend_fail(['message' => 'Quantity is greater than stock.'], JsonResponse::HTTP_BAD_REQUEST);
                    }
                    $stock->save();
                }

                array_push($saved_data, $damage_item);
            }
            DB::commit();
            return jsend_success(DamageItemResource::collection($saved_data), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(__('api.saved-failed', ['item' => class_basename($this->model)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(
                __('api.saved-failed', ['item' => class_basename($this->model)]),
                $ex->getCode(),
                ErrorType::SAVE_ERROR
            );
        }
    }
}

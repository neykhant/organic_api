<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerUsedItemRequest;
use App\Http\Resources\OwnerUsedItemResource;
use App\Models\OwnerUsedItem;
use App\Models\Stock;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OwnerUsedItemController extends Controller
{

    const DATE = 'date';
    const STOCK_ID = 'stock_id';
    const QUANTITY = 'quantity';

    private $model;

    public function __construct(OwnerUsedItem $model)
    {
        $this->model = $model;
    }

    public function index()
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;

        if ($shop_id == 0) {
            $data = $this->model::OrderBy('created_at', 'desc')->get();
            return response()->json(["status" => "success", "data" => OwnerUsedItemResource::collection($data), "total" => count($data)]);
        }

        $data = $this->model::where('shop_id', '=', $shop_id)->orderBy('created_at', 'desc')->get();
        return response()->json(["status" => "success", "data" => OwnerUsedItemResource::collection($data), "total" => count($data)]);
    }

    public function store(OwnerUsedItemRequest $request)
    {
        DB::beginTransaction();
        try {
            $date = trim($request->get(self::DATE));
            $stock_id = trim($request->get(self::STOCK_ID));
            $quantity = trim($request->get(self::QUANTITY));

            $user = Auth::user();
            $shop_id = $user->shop_id;

            $model = new $this->model;
            $model->date = $date;
            $model->stock_id = $stock_id;
            $model->quantity = $quantity;
            $model->shop_id = $shop_id;

            $model->save();


            $stock = Stock::find($stock_id);
            if ($stock->quantity >= $quantity) {
                $stock->quantity -= $quantity;
            } else {
                return jsend_fail(['message' => 'Quantity is greater than stock.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $stock->save();


            DB::commit();
            return jsend_success(new OwnerUsedItemResource($model), JsonResponse::HTTP_CREATED);
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

    public function show(OwnerUsedItem $owner_used_item)
    {
        return jsend_success(new OwnerUsedItemResource($owner_used_item));
    }

    public function update(OwnerUsedItemRequest $request, OwnerUsedItem $owner_used_item)
    {
        DB::beginTransaction();
        try {
            $date = trim($request->get(self::DATE));
            $stock_id = trim($request->get(self::STOCK_ID));
            $quantity = trim($request->get(self::QUANTITY));

            $user = Auth::user();
            $shop_id = $user->shop_id;

            $stock = Stock::find($stock_id);
            if ($owner_used_item->quantity > $quantity) {
                $stock->quantity += $owner_used_item->quantity - $quantity;
                $stock->save();
            } else {
                if ($stock->quantity >= ($quantity - $owner_used_item->quantity)) {
                    $stock->quantity -= $quantity - $owner_used_item->quantity;
                } else {
                    return jsend_fail(['message' => 'Quantity is greater than stock.'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $stock->save();
            }


            $owner_used_item->date = $date;
            $owner_used_item->stock_id = $stock_id;
            $owner_used_item->quantity = $quantity;
            $owner_used_item->shop_id = $shop_id;
            $owner_used_item->save();

            DB::commit();
            return jsend_success(new OwnerUsedItemResource($owner_used_item), JsonResponse::HTTP_CREATED);
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

    public function destroy(OwnerUsedItem $owner_used_item)
    {

        DB::beginTransaction();
        try {

            $stock = Stock::find($owner_used_item->stock_id);
            $stock->quantity += $owner_used_item->quantity;
            $stock->save();

            $owner_used_item->delete();

            DB::commit();
            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}

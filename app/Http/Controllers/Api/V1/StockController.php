<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockResource;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StockRequest;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Log;
use Request;

class StockController extends Controller
{

    const ITEM = 'item_id';
    const QTY = 'quantity';
    const SHOP_ID = 'shop_id';
    const STOCKS = 'stocks';

    private $model;

    public function __construct(Stock $model)
    {
        $this->model = $model;
    }

    public function index()
    {
        $user = Auth::user();
        // $shop_id = $user->shop_id;
        $shop_id = 1;

        if ($shop_id == 0) {
            $data = Stock::all();
            return response()->json(["status" => "success", "data" => StockResource::collection($data), "total" => count($data)]);
        }

        $data = Stock::where('shop_id', '=', $shop_id)->get();
        return response()->json(["status" => "success", "data" => StockResource::collection($data), "total" => count($data)]);
    }

    public function store(StockRequest $request)
    {
        DB::beginTransaction();
        try {
            $saved_data = [];
            $stocks = $request->get(self::STOCKS);
            foreach ($stocks as $stock) {
                $stock_all = new Stock();
                $stock_all->quantity = $stock['quantity'];
                $stock_all->item_id = $stock['item_id'];
                $stock_all->shop_id = $stock['shop_id'];
                $stock_all->save();
                array_push($saved_data, $stock_all);
            }
            DB::commit();
            return jsend_success(StockResource::collection($saved_data), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(__('api.saved-failed', ['model' => class_basename(Stock::class)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(__('api.saved-failed', ['model' => class_basename(Stock::class)]), [
                $ex->getCode(),
                ErrorType::SAVE_ERROR,
            ]);
        }
    }

    public function show(Stock $stock)
    {
        return jsend_success(new StockResource($stock));
    }

    public function update(StockRequest $request, Stock $stock)
    {
        try {
            $stock->quantity =  trim($request->input(self::QTY));
            $stock->item_id =  trim($request->input(self::ITEM));
            $stock->shop_id =  trim($request->input(self::SHOP_ID));
            $stock->save();
            return jsend_success(new StockResource($stock), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {
            Log::error(__('api.updated-failed', ['model' => class_basename($this->model)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(__('api.updated-failed', ['model' => class_basename($this->model)]), [
                $ex->getCode(),
                ErrorType::UPDATE_ERROR,
            ]);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $stock = Stock::findOrFail($id);
            $stock->delete();
            DB::commit();
            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (Exception $ex) {
            DB::rollBack();
            return jsend_error(__('api.deleted-failed', ['model' => class_basename(Stock::class)]), [
                $ex->getCode(),
                ErrorType::DELETE_ERROR,
            ]);
        }
    }
}

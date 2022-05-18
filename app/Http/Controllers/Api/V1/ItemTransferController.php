<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItemTransferRequest;
use App\Http\Resources\ItemTransferResource;
use App\Models\ItemTransfer;
use App\Models\Stock;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemTransferController extends Controller
{
    const TO_SHOP_ID = 'to_shop_id';
    const STOCK_ID = 'stock_id';
    const QUANTITY = 'quantity';
    const ITEM_TRANSFERS = 'item_transfers';

    public function index()
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;

        if ($shop_id == 0) {
            $data = ItemTransfer::OrderBy('created_at', 'desc')->get();
            return response()->json(["status" => "success", "data" => ItemTransferResource::collection($data), "total" => count($data)]);
        }

        $data = ItemTransfer::where(function ($query) use ($shop_id) {
            $query->where("shop_id", $shop_id)->orWhere("to_shop_id", $shop_id);
        })->orderBy('created_at', 'desc')->get();
        return response()->json(["status" => "success", "data" => ItemTransferResource::collection($data), "total" => count($data)]);
    }

    public function store()
    {
        DB::beginTransaction();
        try {
            $to_shop_id = trim(request()->get(self::TO_SHOP_ID));
            $stock_id = trim(request()->get(self::STOCK_ID));
            $quantity = trim(request()->get(self::QUANTITY));

            $user = Auth::user();
            $shop_id = $user->shop_id;

            $stock = Stock::find($stock_id);
            if ($stock->quantity >= $quantity) {
                $item_transfer = new ItemTransfer();
                $item_transfer->to_shop_id = $to_shop_id;
                $item_transfer->shop_id = $shop_id;
                $item_transfer->stock_id = $stock_id;
                $item_transfer->quantity = $quantity;
                $item_transfer->save();

                $stock->quantity -= $quantity;
                $stock->save();

                $item_transfer_stock = Stock::where('item_id', '=', $stock->item_id)->where('shop_id', '=', $to_shop_id)->first();
                if ($item_transfer_stock == null) {
                    $new_stock = new Stock();
                    $new_stock->quantity = $quantity;
                    $new_stock->item_id = $stock->item_id;
                    $new_stock->shop_id = $to_shop_id;

                    $new_stock->save();
                } else {
                    $item_transfer_stock->quantity += $quantity;

                    $item_transfer_stock->save();
                }

                DB::commit();
                return jsend_success(new ItemTransferResource($item_transfer), JsonResponse::HTTP_CREATED);
            } else {
                return jsend_fail(['message' => 'Quantity is greater than stock.'], JsonResponse::HTTP_BAD_REQUEST);
            }
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(__('api.saved-failed', ['model' => class_basename(ItemTransfer::class)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(__('api.saved-failed', ['model' => class_basename(ItemTransfer::class)]), [
                $ex->getCode(),
                ErrorType::SAVE_ERROR,
            ]);
        }
    }

    public function batchInsert(ItemTransferRequest $request)
    {
        DB::beginTransaction();
        try {
            $to_shop_id = trim($request->get(self::TO_SHOP_ID));
            $user = Auth::user();
            $shop_id = $user->shop_id;

            $item_transfers = $request->get(self::ITEM_TRANSFERS);

            $saved_data = [];
            foreach ($item_transfers as $item_transfer) {

                $stock = Stock::find($item_transfer['stock_id']);
                if ($stock->quantity >= $item_transfer['quantity']) {
                    $item_transfer_model = new ItemTransfer();
                    $item_transfer_model->to_shop_id = $to_shop_id;
                    $item_transfer_model->shop_id = $shop_id;
                    $item_transfer_model->stock_id = $item_transfer['stock_id'];
                    $item_transfer_model->quantity = $item_transfer['quantity'];
                    $item_transfer_model->save();

                    array_push($saved_data, $item_transfer_model);

                    $stock->quantity -= $item_transfer['quantity'];
                    $stock->save();

                    $item_transfer_stock = Stock::where('item_id', '=', $stock->item_id)->where('shop_id', '=', $to_shop_id)->first();
                    if ($item_transfer_stock == null) {
                        $new_stock = new Stock();
                        $new_stock->quantity = $item_transfer['quantity'];
                        $new_stock->item_id = $stock->item_id;
                        $new_stock->shop_id = $to_shop_id;

                        $new_stock->save();
                    } else {
                        $item_transfer_stock->quantity += $item_transfer['quantity'];

                        $item_transfer_stock->save();
                    }
                } else {
                    return jsend_fail(['message' => 'Quantity is greater than stock.'], JsonResponse::HTTP_BAD_REQUEST);
                }
            }
            DB::commit();
            return jsend_success(ItemTransferResource::collection($saved_data), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(__('api.saved-failed', ['model' => class_basename(ItemTransfer::class)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(__('api.saved-failed', ['model' => class_basename(ItemTransfer::class)]), [
                $ex->getCode(),
                ErrorType::SAVE_ERROR,
            ]);
        }
    }

    public function destroy(ItemTransfer $item_transfer)
    {
        DB::beginTransaction();
        try {

            $stock = Stock::find($item_transfer->stock_id);
            $item_id = $stock->item_id;

            $from_stock = Stock::where('item_id', '=', $item_id)->where('shop_id', '=', $item_transfer->shop_id)->first();

            $from_stock->quantity += $item_transfer->quantity;
            $from_stock->save();

            $to_stock = Stock::where('item_id', '=', $item_id)->where('shop_id', '=', $item_transfer->to_shop_id)->first();

            $to_stock->quantity -= $item_transfer->quantity;
            $to_stock->save();

            $item_transfer->delete();

            DB::commit();
            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (Exception $ex) {
            DB::rollBack();
            return jsend_error(__('api.deleted-failed', ['model' => class_basename(ItemTransfer::class)]), [
                $ex->getCode(),
                ErrorType::DELETE_ERROR,
            ]);
        }
    }
}

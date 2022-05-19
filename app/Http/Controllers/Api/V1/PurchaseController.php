<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    const PURCHASE_ITEMS = "purchase_items";


    public function index()
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;

        if ($shop_id == 0) {
            $data = Purchase::OrderBy('created_at', 'desc')->get();
            return response()->json(["status" => "success", "data" => PurchaseResource::collection($data), "total" => count($data)]);
        }

        $data = Purchase::where('shop_id', '=', $shop_id)->orderBy('created_at', 'desc')->get();
        return response()->json(["status" => "success", "data" => PurchaseResource::collection($data), "total" => count($data)]);
    }

    public function store(PurchaseRequest $request)
    {

        DB::beginTransaction();
        try {

            $date = $request->get('date');
            $merchant_id = $request->get('merchant_id');
            $whole_total = $request->get('whole_total');
            $paid = $request->get('paid');


            $user = Auth::user();
            // $shop_id = $user->shop_id;
            $shop_id = 1;

            $purchase = new Purchase();
            $purchase->date = $date;
            $purchase->merchant_id = $merchant_id;
            $purchase->whole_total = $whole_total;
            $purchase->paid = $paid;
            $purchase->credit = $whole_total - $paid;
            $purchase->shop_id = $shop_id;
            $purchase->save();

            $purchase_items = $request->get(self::PURCHASE_ITEMS);

            $saved_data = [];
            foreach ($purchase_items as $purchase_item) {
                $purchase_item_model = new PurchaseItem();
                $purchase_item_model->purchase_id = $purchase->id;
                $purchase_item_model->item_id = $purchase_item["item_id"];
                $purchase_item_model->price = $purchase_item["price"];
                $purchase_item_model->quantity = $purchase_item["quantity"];
                $purchase_item_model->subtotal = $purchase_item["subtotal"];
                array_push($saved_data, $purchase_item_model);
                $purchase_item_model->save();
            }

            return "success";

            DB::commit();
            return jsend_success(new PurchaseResource($purchase), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {

            DB::rollBack();
            Log::error(__('api.saved-failed', ['model' => class_basename(Purchase::class)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(__('api.saved-failed', ['model' => class_basename(Purchase::class)]), [
                $ex->getCode(),
                ErrorType::SAVE_ERROR,
            ]);
        }
    }

    public function show(Purchase $purchase)
    {

        return jsend_success(new PurchaseResource($purchase));
    }

    public function update(PurchaseRequest $request, Purchase $purchase)
    {
        DB::beginTransaction();
        try {

            $date = $request->get('date');
            $merchant_id = $request->get('merchant_id');
            $whole_total = $request->get('whole_total');
            $paid = $request->get('paid');
            $purchase_items = $request->get('purchase_items');

            $user = Auth::user();
            $shop_id = $user->shop_id;

            $purchase->date = $date;
            $purchase->merchant_id = $merchant_id;
            $purchase->whole_total = $whole_total;
            $purchase->paid = $paid;
            $purchase->credit = $whole_total - $paid;
            $purchase->shop_id = $shop_id;

            $purchase->save();

            foreach ($purchase_items as $purchase_item) {
                if ($purchase_item["id"] == 0) {


                    $purchase_item_model = new PurchaseItem();
                    $purchase_item_model->purchase_id = $purchase->id;
                    $purchase_item_model->item_id = $purchase_item["item_id"];
                    $purchase_item_model->price = $purchase_item["price"];
                    $purchase_item_model->quantity = $purchase_item["quantity"];
                    $purchase_item_model->subtotal = $purchase_item["subtotal"];

                    $purchase_item_model->save();

                    $stock = Stock::where('item_id', '=', $purchase_item_model->item_id)->where('shop_id', '=', $shop_id)->first();
                    if ($stock == null) {
                        $new_stock = new Stock();
                        $new_stock->quantity = $purchase_item_model->quantity;
                        $new_stock->item_id = $purchase_item_model->item_id;
                        $new_stock->shop_id = $shop_id;

                        $new_stock->save();
                    } else {
                        $stock->quantity += $purchase_item_model->quantity;

                        $stock->save();
                    }
                } else {

                    $purchase_item_model = PurchaseItem::find($purchase_item["id"]);

                    $stock = Stock::where('item_id', '=', $purchase_item_model->item_id)->where('shop_id', '=', $shop_id)->first();
                    if ($purchase_item_model->quantity > $purchase_item["quantity"]) {
                        $quantity = $purchase_item_model->quantity - $purchase_item["quantity"];
                        $stock->quantity -= $quantity;
                    } else {
                        $quantity = $purchase_item["quantity"] - $purchase_item_model->quantity;
                        $stock->quantity += $quantity;
                    }
                    $stock->save();

                    $purchase_item_model->purchase_id = $purchase->id;
                    $purchase_item_model->item_id = $purchase_item["item_id"];
                    $purchase_item_model->price = $purchase_item["price"];
                    $purchase_item_model->quantity = $purchase_item["quantity"];
                    $purchase_item_model->subtotal = $purchase_item["subtotal"];

                    $purchase_item_model->save();
                }
            }
            DB::commit();
            return jsend_success(new PurchaseResource($purchase), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(__('api.saved-failed', ['model' => class_basename(Purchase::class)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(__('api.saved-failed', ['model' => class_basename(Purchase::class)]), [
                $ex->getCode(),
                ErrorType::SAVE_ERROR,
            ]);
        }
    }

    public function destroy(Purchase $purchase)
    {
        try {

            $purchase->delete();

            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (Exception $ex) {
            return jsend_error(__('api.deleted-failed', ['model' => class_basename(Purchase::class)]), [
                $ex->getCode(),
                ErrorType::DELETE_ERROR,
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param PurchaseItem  $purchase_item
     * @return \Illuminate\Http\Response
     */
    public function deletePurchaseItem(PurchaseItem $purchase_item)
    {
        DB::beginTransaction();
        try {
            $purchase = Purchase::find($purchase_item->purchase_id);

            $stock = Stock::where('item_id', '=', $purchase_item->item_id)->where('shop_id', '=', $purchase->shop_id)->first();
            $stock->quantity -= $purchase_item->quantity;

            $stock->save();

            $purchase->whole_total -= $purchase_item->subtotal;
            $purchase->credit = $purchase->whole_total - $purchase->paid;
            $purchase->save();

            $purchase_item->delete();

            DB::commit();
            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    public function reportPurchase()
    {
        $purchases = Purchase::with('merchant');

        $purchases = $purchases->groupBy("merchant_id")->select(DB::raw('sum(whole_total) as whole_total'), DB::raw('sum(paid) as paid'), DB::raw('sum(credit) as credit'), 'merchant_id');

        if (request()->start_date && request()->end_date) {
            $purchases = $purchases->whereBetween("created_at", [request()->start_date . " 00:00:00", request()->end_date . " 23:59:59"]);
        }

        $purchases = $purchases->get();
        return jsend_success($purchases, JsonResponse::HTTP_CREATED);
    }
}

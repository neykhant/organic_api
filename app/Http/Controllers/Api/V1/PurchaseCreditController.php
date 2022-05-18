<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseCreditRequest;
use App\Models\Purchase;
use App\Models\PurchaseCredit;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseCreditController extends Controller
{
    const PURCHASE_ID = 'purchase_id';
    const AMOUNT = 'amount';

    public function store(PurchaseCreditRequest $request)
    {
        DB::beginTransaction();
        try {

            $purchase_id = trim($request->get(self::PURCHASE_ID));
            $amount = trim($request->get(self::AMOUNT));
            
            $purchase = Purchase::find($purchase_id);
            if($purchase->credit >= $amount){
                $purchase_credit = new PurchaseCredit();
                $purchase_credit->purchase_id = $purchase_id;
                $purchase_credit->amount = $amount;

                $purchase_credit->save();

                $purchase->credit -= $amount;

                $purchase->save();

                DB::commit();
                return jsend_success($purchase_credit, JsonResponse::HTTP_CREATED);
            }else{
                return jsend_fail(['message' => 'Amount is greater than credit.'], JsonResponse::HTTP_BAD_REQUEST);
            }

        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(__('api.saved-failed', ['model' => class_basename(PurchaseCredit::class)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(__('api.saved-failed', ['model' => class_basename(PurchaseCredit::class)]), [
                $ex->getCode(),
                ErrorType::SAVE_ERROR,
            ]);
        }
    }

    public function destroy(PurchaseCredit $purchase_credit)
    {
        DB::beginTransaction();
        try {
            $purchase = Purchase::find($purchase_credit->purchase_id);
            $purchase->credit += $purchase_credit->amount;

            $purchase->save();

            $purchase_credit->delete();

            DB::commit();
            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (Exception $ex) {
            DB::rollBack();
            return jsend_error(__('api.deleted-failed', ['model' => class_basename(PurchaseCredit::class)]), [
                $ex->getCode(),
                ErrorType::DELETE_ERROR,
            ]);
        }
    }
}

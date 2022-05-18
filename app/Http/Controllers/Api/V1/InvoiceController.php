<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceService;
use App\Models\Item;
use App\Models\Stock;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    const DATE = 'date';
    const MEMBER_ID = 'member_id';
    const ITEM_BUY_TOTAL = 'item_buy_total';
    const ITEM_TOTAL = 'item_total';
    const ITEMS = 'items';
    const SERVICE_TOTAL = 'service_total';
    const SERVICES = 'services';
    const TOTAL = 'total';
    const DISCOUNT = 'discount';
    const PAID = 'paid';
    const CUSTOMER_NAME = 'customer_name';
    const CUSTOMER_PHONE_NO = 'customer_phone_no';
    const PAYMENT_METHOD = 'payment_method';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     **/
    public function index()
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;

        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');

        if ($shop_id == 0) {
            if (request()->has('start_date') && request()->has('end_date')) {
                $data = Invoice::OrderBy('created_at', 'desc')->whereBetween('created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])->get();
                return response()->json(["status" => "success", "data" => InvoiceResource::collection($data), "total" => count($data)]);
            } else {
                $data = Invoice::OrderBy('created_at', 'desc')->get();
                return response()->json(["status" => "success", "data" => InvoiceResource::collection($data), "total" => count($data)]);
            }
        }

        if (request()->has('start_date') && request()->has('end_date')) {
            $data = Invoice::where('shop_id', '=', $shop_id)->orderBy('created_at', 'desc')->whereBetween('created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])->get();
            return response()->json(["status" => "success", "data" => InvoiceResource::collection($data), "total" => count($data)]);
        } else {
            $data = Invoice::where('shop_id', '=', $shop_id)->orderBy('created_at', 'desc')->get();
            return response()->json(["status" => "success", "data" => InvoiceResource::collection($data), "total" => count($data)]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(InvoiceRequest $request)
    {
        DB::beginTransaction();
        try {
            $date = trim($request->input(self::DATE));
            $member_id = trim($request->input(self::MEMBER_ID));
            $item_buy_total = trim($request->input(self::ITEM_BUY_TOTAL));
            $item_total = trim($request->input(self::ITEM_TOTAL));
            $items = $request->input(self::ITEMS);
            $service_total = trim($request->input(self::SERVICE_TOTAL));
            $services = $request->input(self::SERVICES);
            $total = trim($request->input(self::TOTAL));
            $discount = trim($request->input(self::DISCOUNT));
            $paid = trim($request->input(self::PAID));
            $customer_name = trim($request->input(self::CUSTOMER_NAME));
            $customer_phone_no = trim($request->input(self::CUSTOMER_PHONE_NO));
            $payment_method = trim($request->input(self::PAYMENT_METHOD));

            $user = Auth::user();
            $shop_id = $user->shop_id;

            $invoice = new Invoice();
            $invoice->date = $date;
            if ($request->has(self::MEMBER_ID)) {
                $invoice->member_id = $member_id;
            }
            $invoice->item_buy_total = $item_buy_total;
            $invoice->item_total = $item_total;
            $invoice->service_total = $service_total;
            $invoice->total = $total;
            $invoice->discount = $discount;
            $invoice->final_total = $total - ($total * ($discount / 100));
            $invoice->paid = $paid;
            $invoice->credit = $invoice->final_total - $paid;
            if ($request->has(self::CUSTOMER_NAME)) {
                $invoice->customer_name = $customer_name;
            }
            if ($request->has(self::CUSTOMER_PHONE_NO)) {
                $invoice->customer_phone_no = $customer_phone_no;
            }
            $invoice->payment_method = $payment_method;
            $invoice->shop_id = $shop_id;

            $invoice->save();

            foreach ($items as $item) {
                $invoice_item = new InvoiceItem();
                $invoice_item->stock_id = $item['stock_id'];

                $stock_model = Stock::find($item['stock_id']);
                $invoice_item->item_id = $stock_model->item_id;

                
                $invoice_item->price = $item['price'];
                $invoice_item->quantity = $item['quantity'];
                $invoice_item->subtotal = $item['price'] * $item['quantity'];
                $invoice_item->invoice_id = $invoice->id;

                $invoice_item->save();

                $stock = Stock::find($invoice_item->stock_id);
                if ($stock->quantity < $invoice_item->quantity) {
                    return jsend_fail(['message' => 'Quantity is greater than stock.'], JsonResponse::HTTP_BAD_REQUEST);
                }
                $stock->quantity -= $invoice_item->quantity;
                $stock->save();
            }

            foreach ($services as $service) {
                $invoice_service = new InvoiceService();
                $invoice_service->service_id = $service['service_id'];
                $invoice_service->staff_id = $service['staff_id'];
                $invoice_service->price = $service['price'];
                $invoice_service->quantity = $service['quantity'];
                $invoice_service->subtotal = $service['price'] * $service['quantity'];
                $invoice_service->invoice_id = $invoice->id;

                $invoice_service->save();
            }

            DB::commit();
            return jsend_success(new InvoiceResource($invoice), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(__('api.saved-failed', ['invoice' => class_basename(Invoice::class)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(
                __('api.saved-failed', ['invoice' => class_basename(Invoice::class)]),
                $ex->getCode(),
                ErrorType::SAVE_ERROR
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        return jsend_success(new InvoiceResource($invoice));
    }

    public function update(InvoiceRequest $request, Invoice $invoice)
    {
        DB::beginTransaction();
        try {
            $date = trim($request->input(self::DATE));
            $member_id = trim($request->input(self::MEMBER_ID));
            $item_buy_total = trim($request->input(self::ITEM_BUY_TOTAL));
            $item_total = trim($request->input(self::ITEM_TOTAL));
            $items = $request->input(self::ITEMS);
            $service_total = trim($request->input(self::SERVICE_TOTAL));
            $services = $request->input(self::SERVICES);
            $total = trim($request->input(self::TOTAL));
            $discount = trim($request->input(self::DISCOUNT));
            $paid = trim($request->input(self::PAID));
            $customer_name = trim($request->input(self::CUSTOMER_NAME));
            $customer_phone_no = trim($request->input(self::CUSTOMER_PHONE_NO));
            $payment_method = trim($request->input(self::PAYMENT_METHOD));

            $user = Auth::user();
            $shop_id = $user->shop_id;

            $invoice->date = $date;
            if ($request->has(self::MEMBER_ID)) {
                $invoice->member_id = $member_id;
            }
            $invoice->item_buy_total = $item_buy_total;
            $invoice->item_total = $item_total;
            $invoice->service_total = $service_total;
            $invoice->total = $total;
            $invoice->discount = $discount;
            $invoice->final_total = $total - ($total * ($discount / 100));
            $invoice->paid = $paid;
            $invoice->credit = $invoice->final_total - $paid;
            if ($request->has(self::CUSTOMER_NAME)) {
                $invoice->customer_name = $customer_name;
            }
            if ($request->has(self::CUSTOMER_PHONE_NO)) {
                $invoice->customer_phone_no = $customer_phone_no;
            }
            $invoice->payment_method = $payment_method;
            $invoice->shop_id = $shop_id;

            $invoice->save();

            foreach ($items as $item) {
                if ($item["id"] == 0) {

                    $invoice_item = new InvoiceItem();
                    $invoice_item->stock_id = $item['stock_id'];

                    $stock_model = Stock::find($item['stock_id']);
                    $invoice_item->item_id = $stock_model->item_id;

                    
                    $invoice_item->price = $item['price'];
                    $invoice_item->quantity = $item['quantity'];
                    $invoice_item->subtotal = $item['price'] * $item['quantity'];
                    $invoice_item->invoice_id = $invoice->id;

                    $invoice_item->save();

                    $stock = Stock::find($invoice_item->stock_id);
                    if ($stock->quantity < $invoice_item->quantity) {
                        return jsend_fail(['message' => 'Quantity is greater than stock.'], JsonResponse::HTTP_BAD_REQUEST);
                    }
                    $stock->quantity -= $invoice_item->quantity;
                    $stock->save();
                } else {

                    $invoice_item = InvoiceItem::find($item["id"]);

                    $stock = Stock::find($invoice_item->stock_id);
                    if ($invoice_item->quantity > $item["quantity"]) {
                        $quantity = $invoice_item->quantity - $item["quantity"];
                        $stock->quantity += $quantity;
                    } else {
                        $quantity = $item["quantity"] - $invoice_item->quantity;
                        if ($stock->quantity < $quantity) {
                            return jsend_fail(['message' => 'Quantity is greater than stock.'], JsonResponse::HTTP_BAD_REQUEST);
                        }
                        $stock->quantity -= $quantity;
                    }

                    $stock->save();

                    $invoice_item->stock_id = $item['stock_id'];

                    $stock_model = Stock::find($item['stock_id']);
                    $invoice_item->item_id = $stock_model->item_id;

                    $invoice_item->staff_id = $item['staff_id'];
                    $invoice_item->price = $item['price'];
                    $invoice_item->quantity = $item['quantity'];
                    $invoice_item->subtotal = $item['price'] * $item['quantity'];
                    $invoice_item->invoice_id = $invoice->id;

                    $invoice_item->save();
                }
            }

            foreach ($services as $service) {
                $invoice_service = $service["id"] == 0 ? new InvoiceService() : InvoiceService::find($service["id"]);
                $invoice_service->service_id = $service['service_id'];
                $invoice_service->staff_id = $service['staff_id'];
                $invoice_service->price = $service['price'];
                $invoice_service->quantity = $service['quantity'];
                $invoice_service->subtotal = $service['price'] * $service['quantity'];
                $invoice_service->invoice_id = $invoice->id;

                $invoice_service->save();
            }



            DB::commit();
            return jsend_success(new InvoiceResource($invoice), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(__('api.saved-failed', ['invoice' => class_basename(Invoice::class)]), [
                'code' => $ex->getCode(),
                'trace' => $ex->getTrace(),
            ]);

            return jsend_error(
                __('api.saved-failed', ['invoice' => class_basename(Invoice::class)]),
                $ex->getCode(),
                ErrorType::SAVE_ERROR
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {

        DB::beginTransaction();
        try {
            $invoice_items = $invoice->invoiceItems;
            foreach ($invoice_items as $invoice_item) {
                $stock = Stock::find($invoice_item->stock_id);
                $stock->quantity += $invoice_item->quantity;

                $stock->save();
            }

            $invoice->delete();

            DB::commit();
            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  InvoiceItem  $invoice_item
     * @return \Illuminate\Http\Response
     */
    public function deleteInvoiceItem(InvoiceItem $invoice_item)
    {
        DB::beginTransaction();
        try {
            $stock = Stock::find($invoice_item->stock_id);
            $stock->quantity += $invoice_item->quantity;

            $stock->save();

            $item = Item::find($stock->item_id);

            $invoice = Invoice::find($invoice_item->invoice_id);
            $invoice->item_buy_total -= $item->buy_price * $invoice_item->quantity;
            $invoice->item_total -= $invoice_item->subtotal;
            $invoice->total -= $invoice_item->subtotal;
            $invoice->final_total = $invoice->total - ($invoice->total * ($invoice->discount / 100));
            $invoice->credit = $invoice->final_total - $invoice_item->paid;

            $invoice->save();

            $invoice_item->delete();

            DB::commit();
            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  InvoiceService  $invoice_service
     * @return \Illuminate\Http\Response
     */
    public function deleteInvoiceService(InvoiceService $invoice_service)
    {
        DB::beginTransaction();
        try {

            $invoice = Invoice::find($invoice_service->invoice_id);
            $invoice->service_total -= $invoice_service->subtotal;
            $invoice->total -= $invoice_service->subtotal;
            $invoice->final_total = $invoice->total - ($invoice->total * ($invoice->discount / 100));
            $invoice->credit = $invoice->final_total - $invoice_service->paid;

            $invoice->save();

            $invoice_service->delete();
            DB::commit();
            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    public function daily()
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;

        if (request()->has('start_date') && request()->has('end_date')) {
            $start_date = request()->input('start_date');
            $end_date = request()->input('end_date');

            $daily = Invoice::select(
                DB::raw('Date(created_at) as day'),
                DB::raw('Sum(item_buy_total) as item_buy_total'),
                DB::raw('Sum(item_total) as item_total'),
                DB::raw('Sum(service_total) as service_total'),
                DB::raw('Sum(total) as total'),
                DB::raw('Sum(final_total) as final_total'),
                DB::raw('Sum(credit) as credit'),
                DB::raw('Sum(paid) as paid'),
                DB::raw('Sum(item_total - item_buy_total) as item_gross_profit')
            )->groupBy('day')
                ->orderBy('day', 'desc')
                ->where('shop_id', $shop_id)
                ->whereBetween('created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
                ->get();
        } else {
            $daily = Invoice::select(
                DB::raw('Date(created_at) as day'),
                DB::raw('Sum(item_buy_total) as item_buy_total'),
                DB::raw('Sum(item_total) as item_total'),
                DB::raw('Sum(service_total) as service_total'),
                DB::raw('Sum(total) as total'),
                DB::raw('Sum(final_total) as final_total'),
                DB::raw('Sum(credit) as credit'),
                DB::raw('Sum(paid) as paid'),
                DB::raw('Sum(item_total - item_buy_total) as item_gross_profit')
            )->groupBy('day')
                ->orderBy('day', 'desc')
                ->where('shop_id', $shop_id)
                ->get();
        }
        return response()->json(["status" => "success", "data" => $daily, "total" => count($daily)]);
    }
}

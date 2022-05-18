<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    const CODE = 'code';
    const CATEGORY = 'category';
    const PRICE = 'price';
    const PERCENTAGE = 'percentage';

    private $model;

    public function __construct(Service $model)
    {
        $this->model = $model;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = $this->model::all();
        return response()->json(["status" => "success", "data" => ServiceResource::collection($data), "total" => count($data)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\ServiceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ServiceRequest $request)
    {
        try {
            $code = trim($request->get(self::CODE));
            $category = trim($request->get(self::CATEGORY));
            $price = trim($request->get(self::PRICE));
            $percentage = trim($request->get(self::PERCENTAGE));

            $model = new $this->model;
            $model->code = $code;
            $model->category = $category;
            $model->price = $price;
            $model->percentage = $percentage;
            $model->commercial = $price * ($percentage / 100);

            $model->save();

            return jsend_success(new ServiceResource($model), JsonResponse::HTTP_CREATED);
        } catch (Exception $ex) {
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

    /**
     * Display the specified resource.
     *
     * @param  Service  $service
     * @return \Illuminate\Http\Response
     */
    public function show(Service $service)
    {
        return jsend_success(new ServiceResource($service));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\ServiceRequest  $request
     * @param  Service  $service
     * @return \Illuminate\Http\Response
     */
    public function update(ServiceRequest $request, Service $service)
    {

        try {
            $code = trim($request->get(self::CODE));
            $category = trim($request->get(self::CATEGORY));
            $price = trim($request->get(self::PRICE));
            $percentage = trim($request->get(self::PERCENTAGE));

            $service->code = $code;
            $service->category = $category;
            $service->price = $price;
            $service->percentage = $percentage;
            $service->commercial = $price * ($percentage / 100);

            $service->save();

            return jsend_success(new ServiceResource($service), JsonResponse::HTTP_CREATED);
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  Service  $service
     * @return \Illuminate\Http\Response
     */
    public function destroy(Service $service)
    {

        try {
            $service->delete();

            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    public function bestService(){
        $user = Auth::user();
        $shop_id = $user->shop_id;

        if($shop_id == 0){
            $services=\App\Models\InvoiceService::with(['service','invoice']);
            if (request()->best){
                $services=$services->groupBy("service_id")->select(DB::raw('count(quantity) as total_qty'),DB::raw('count(quantity) as total_subtotal'),'service_id');
            }
    
            if (request()->start_date && request()->end_date) {
                $services = $services->whereBetween("created_at", [request()->start_date, request()->end_date]);
            }
           
            
            $services=$services->get();
            return jsend_success($services, JsonResponse::HTTP_CREATED);
        }
        
        $services=\App\Models\InvoiceService::with(['service','invoice']);
        if (request()->best){
            $services=$services->groupBy("service_id")->select(DB::raw('count(quantity) as total_qty'),DB::raw('count(quantity) as total_subtotal'),'service_id');
        }

        if (request()->start_date && request()->end_date) {
            $services = $services->whereBetween("created_at", [request()->start_date ." 00:00:00", request()->end_date ." 23:59:59"]);
        }
       
        
        $services=$services->whereHas("invoice",function($query){
            $query->where("shop_id",request()->user()->shop_id);
        })->get();
        return jsend_success($services, JsonResponse::HTTP_CREATED);
    }

    public function batchInsert()
    {
        request()->validate([
            'services' => 'required',
        ]);
        $data = request("services");

        DB::beginTransaction();
        try {
            $saved_data = [];

            foreach ($data as $index => $d) {
                $service = new $this->model;
                $service->code = trim($d["code"]);
                $service->category = trim($d["category"]);
                $service->price = $d['price'];
                $service->percentage = $d['percentage'];
                $service->commercial = $d['price'] * ($d['percentage'] / 100);
                $service->save();

                array_push($saved_data, $service);
            }

            DB::commit();
            return jsend_success(ServiceResource::collection($saved_data), JsonResponse::HTTP_CREATED);
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

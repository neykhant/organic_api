<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantRequest;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MerchantController extends Controller
{
    const CODE = 'code';
    const NAME = 'name';
    const PHONE = 'phone';
    const COMPANY_NAME = 'company_name';
    const OTHER = 'other';

    private $model;

    public function __construct(Merchant $model)
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
        return response()->json(["status" => "success", "data" => MerchantResource::collection($data), "total" => count($data)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\MerchantRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(MerchantRequest $request)
    {
        try {
            $code = trim($request->get(self::CODE));
            $name = trim($request->get(self::NAME));
            $phone = trim($request->get(self::PHONE));
            $company_name = trim($request->get(self::COMPANY_NAME));
            $other = trim($request->get(self::OTHER));

            $model = new $this->model;
            $model->code = $code;
            $model->name = $name;
            $model->phone = $phone;
            $model->company_name = $company_name;
            $model->other = $other;

            $model->save();

            return jsend_success(new MerchantResource($model), JsonResponse::HTTP_CREATED);
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
     * @param  Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function show(Merchant $merchant)
    {
        return jsend_success(new MerchantResource($merchant));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\MerchantRequest  $request
     * @param  Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function update(MerchantRequest $request, Merchant $merchant)
    {

        try {
            $code = trim($request->get(self::CODE));
            $name = trim($request->get(self::NAME));
            $phone = trim($request->get(self::PHONE));
            $company_name = trim($request->get(self::COMPANY_NAME));
            $other = trim($request->get(self::OTHER));

            $merchant->code = $code;
            $merchant->name = $name;
            $merchant->phone = $phone;
            $merchant->company_name = $company_name;
            $merchant->other = $other;

            $merchant->save();

            return jsend_success(new MerchantResource($merchant), JsonResponse::HTTP_CREATED);
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
     * @param  Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function destroy(Merchant $merchant)
    {

        try {
            $merchant->delete();

            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}

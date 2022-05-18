<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyFeeRequest;
use App\Http\Resources\DailyFeeResource;
use App\Models\DailyFee;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DailyFeeController extends Controller
{
    const STAFF_ID = 'staff_id';
    const AMOUNT = 'amount';
    const MONTH = 'month';
    const YEAR = 'year';

    private $model;

    public function __construct(DailyFee $model)
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
        $data = $this->model::OrderBy('created_at', 'desc')->get();
        return response()->json(["status" => "success", "data" => DailyFeeResource::collection($data), "total" => count($data)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\DailyFeeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DailyFeeRequest $request)
    {
        try {
            $staff_id = trim($request->get(self::STAFF_ID));
            $amount = $request->get(self::AMOUNT);
            $month = trim($request->get(self::MONTH));
            $year = trim($request->get(self::YEAR));

            $model = new $this->model;
            $model->staff_id = $staff_id;
            $model->amount = $amount;
            $model->month = $month;
            $model->year = $year;

            $model->save();

            return jsend_success(new DailyFeeResource($model), JsonResponse::HTTP_CREATED);
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
     * @param  DailyFee  $daily_fee
     * @return \Illuminate\Http\Response
     */
    public function show(DailyFee $daily_fee)
    {
        return jsend_success(new DailyFeeResource($daily_fee));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\DailyFeeRequest  $request
     * @param  DailyFee  $daily_fee
     * @return \Illuminate\Http\Response
     */
    public function update(DailyFeeRequest $request, DailyFee $daily_fee)
    {

        try {
            $staff_id = trim($request->get(self::STAFF_ID));
            $amount = $request->get(self::AMOUNT);
            $month = trim($request->get(self::MONTH));
            $year = trim($request->get(self::YEAR));

            $daily_fee->staff_id = $staff_id;
            $daily_fee->amount = $amount;
            $daily_fee->month = $month;
            $daily_fee->year = $year;

            $daily_fee->save();

            return jsend_success(new DailyFeeResource($daily_fee), JsonResponse::HTTP_CREATED);
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
     * @param  DailyFee  $daily_fee
     * @return \Illuminate\Http\Response
     */
    public function destroy(DailyFee $daily_fee)
    {

        try {
            $daily_fee->delete();

            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}

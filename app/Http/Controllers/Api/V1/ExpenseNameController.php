<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseNameRequest;
use App\Http\Resources\ExpenseNameResource;
use App\Models\ExpenseName;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ExpenseNameController extends Controller
{
    const NAME = 'name';

    private $model;

    public function __construct(ExpenseName $model)
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
        return response()->json(["status" => "success", "data" => ExpenseNameResource::collection($data), "total" => count($data)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\ExpenseNameRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ExpenseNameRequest $request)
    {
        try {
            $name = trim($request->get(self::NAME));

            $model = new $this->model;
            $model->name = $name;

            $model->save();

            return jsend_success(new ExpenseNameResource($model), JsonResponse::HTTP_CREATED);
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
     * @param  ExpenseName  $expense_name
     * @return \Illuminate\Http\Response
     */
    public function show(ExpenseName $expense_name)
    {
        return jsend_success(new ExpenseNameResource($expense_name));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\ExpenseNameRequest  $request
     * @param  ExpenseName  $expense_name
     * @return \Illuminate\Http\Response
     */
    public function update(ExpenseNameRequest $request, ExpenseName $expense_name)
    {

        try {
            $name = trim($request->get(self::NAME));
            $expense_name->name = $name;

            $expense_name->save();

            return jsend_success(new ExpenseNameResource($expense_name), JsonResponse::HTTP_CREATED);
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
     * @param  ExpenseName  $expense_name
     * @return \Illuminate\Http\Response
     */
    public function destroy(ExpenseName $expense_name)
    {

        try {
            $expense_name->delete();

            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}

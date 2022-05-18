<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    const NAME = 'name';
    const AMOUNT = 'amount';

    private $model;

    public function __construct(Expense $model)
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
        $user = Auth::user();
        $shop_id = $user->shop_id;

        if ($shop_id == 0) {
            $data = $this->model::OrderBy('created_at', 'desc')->get();
            return response()->json(["status" => "success", "data" => ExpenseResource::collection($data), "total" => count($data)]);
        }

        $data = $this->model::where('shop_id', '=', $shop_id)->orderBy('created_at', 'desc')->get();
        return response()->json(["status" => "success", "data" => ExpenseResource::collection($data), "total" => count($data)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\ExpenseRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ExpenseRequest $request)
    {
        try {
            $name = trim($request->get(self::NAME));
            $amount = trim($request->get(self::AMOUNT));

            $user = Auth::user();
            $shop_id = $user->shop_id;

            $model = new $this->model;
            $model->name = $name;
            $model->amount = $amount;
            $model->shop_id = $shop_id;

            $model->save();

            return jsend_success(new ExpenseResource($model), JsonResponse::HTTP_CREATED);
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
     * @param  Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function show(Expense $expense)
    {
        return jsend_success(new ExpenseResource($expense));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\ExpenseRequest  $request
     * @param  Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function update(ExpenseRequest $request, Expense $expense)
    {

        try {
            $name = trim($request->get(self::NAME));
            $amount = trim($request->get(self::AMOUNT));

            $expense->name = $name;
            $expense->amount = $amount;

            $expense->save();

            return jsend_success(new ExpenseResource($expense), JsonResponse::HTTP_CREATED);
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
     * @param  Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function destroy(Expense $expense)
    {

        try {
            $expense->delete();

            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    public function batchInsert()
    {
        request()->validate([
            'expenses' => 'required',
        ]);
        $data = request("expenses");

        $user = Auth::user();
        $shop_id = $user->shop_id;

        DB::beginTransaction();
        try {
            $saved_data = [];

            foreach ($data as $index => $d) {
                $expense = new $this->model;
                $expense->name = trim($d["name"]);
                $expense->amount = trim($d["amount"]);
                $expense->shop_id = $shop_id;
                $expense->save();

                array_push($saved_data, $expense);
            }

            DB::commit();
            return jsend_success(ExpenseResource::collection($saved_data), JsonResponse::HTTP_CREATED);
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

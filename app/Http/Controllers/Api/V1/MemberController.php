<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    const CODE = 'code';
    const NAME = 'name';
    const PHONE = 'phone';
    const ADDRESS = 'address';

    private $model;

    public function __construct(Member $model)
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

        if($shop_id == 0){
            $data = $this->model::all();
            return response()->json(["status" => "success", "data" => MemberResource::collection($data), "total" => count($data)]);
        }

        $data = $this->model::where('shop_id', '=', $shop_id)->get();
        return response()->json(["status" => "success", "data" => MemberResource::collection($data), "total" => count($data)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\MemberRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(MemberRequest $request)
    {
        // try {
            $code = trim($request->get(self::CODE));
            $name = trim($request->get(self::NAME));
            $phone = trim($request->get(self::PHONE));
            $address = trim($request->get(self::ADDRESS));

            $user = Auth::user();
            $shop_id = $user->shop_id;

            $model = new $this->model;
            $model->code = $code;
            $model->name = $name;
            $model->phone = $phone;
            $model->address = $address;
            $model->shop_id = $shop_id;

            $model->save();

            return jsend_success(new MemberResource($model), JsonResponse::HTTP_CREATED);
        // } catch (Exception $ex) {
        //     Log::error(__('api.saved-failed', ['model' => class_basename($this->model)]), [
        //         'code' => $ex->getCode(),
        //         'trace' => $ex->getTrace(),
        //     ]);

        //     return jsend_error(__('api.saved-failed', ['model' => class_basename($this->model)]), [
        //         $ex->getCode(),
        //         ErrorType::SAVE_ERROR,
        //     ]);
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  Member  $member
     * @return \Illuminate\Http\Response
     */
    public function show(Member $member)
    {
        return jsend_success(new MemberResource($member));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\MemberRequest  $request
     * @param  Member  $member
     * @return \Illuminate\Http\Response
     */
    public function update(MemberRequest $request, Member $member)
    {

        try {
            $code = trim($request->get(self::CODE));
            $name = trim($request->get(self::NAME));
            $phone = trim($request->get(self::PHONE));
            $address = trim($request->get(self::ADDRESS));

            $member->code = $code;
            $member->name = $name;
            $member->phone = $phone;
            $member->address = $address;

            $member->save();

            return jsend_success(new MemberResource($member), JsonResponse::HTTP_CREATED);
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
     * @param  Member  $member
     * @return \Illuminate\Http\Response
     */
    public function destroy(Member $member)
    {

        try {
            $member->delete();

            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}

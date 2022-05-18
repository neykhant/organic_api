<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffController extends Controller
{
    
    const NAME = 'name';
    const IMAGE = 'image';
    const DOB = 'dob';
    const START_WORK = 'start_work';
    const PHONE = 'phone';
    const SALARY = 'salary';
    const BANK_ACCOUNT = 'bank_account';

    private $model;

    public function __construct(Staff $model)
    {
        $this->model = $model;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     **/
    public function index()
    {
        $user = Auth::user();
        $shop_id = $user->shop_id;

        if($shop_id == 0){
            $data = $this->model::all();
            return response()->json(["status" => "success", "data" => StaffResource::collection($data), "total" => count($data)]);
        }

        $data = $this->model::where('shop_id', '=', $shop_id)->get();
        return response()->json(["status" => "success", "data" => StaffResource::collection($data), "total" => count($data)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'image' => 'required',
            'dob' => 'required',
            'start_work' => 'required',
            'phone' => 'required',
            'salary' => 'required',
            'bank_account' => 'required',
        ]);
        DB::beginTransaction();
        // try {
            $name = trim($request->input(self::NAME));
            $image = $request->file(self::IMAGE);
            $dob = $request->input(self::DOB);
            $start_work = $request->input(self::START_WORK);
            $phone = trim($request->input(self::PHONE));
            $salary = trim($request->input(self::SALARY));
            $bank_account = trim($request->input(self::BANK_ACCOUNT));

            $user = Auth::user();
            $shop_id = $user->shop_id;

            $image_name = FileUploadService::save($image, "staffs");

            $model = new $this->model;
            $model->name = $name;
            $model->image = $image_name;
            $model->dob = $dob;
            $model->start_work = $start_work;
            $model->phone = $phone;
            $model->salary = $salary;
            $model->bank_account = $bank_account;
            $model->shop_id = $shop_id;
            $model->save();

            DB::commit();
            return jsend_success(new StaffResource($model), JsonResponse::HTTP_CREATED);
        // } catch (Exception $ex) {
        //     DB::rollBack();
        //     Log::error(__('api.saved-failed', ['staff' => class_basename($this->model)]), [
        //         'code' => $ex->getCode(),
        //         'trace' => $ex->getTrace(),
        //     ]);

        //     return jsend_error(
        //         __('api.saved-failed', ['staff' => class_basename($this->model)]),
        //         $ex->getCode(),
        //         ErrorType::SAVE_ERROR
        //     );
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  Staff  $staff
     * @return \Illuminate\Http\Response
     */
    public function show(Staff $staff)
    {
        return jsend_success(new StaffResource($staff));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Staff  $staff
     * @return \Illuminate\Http\Response
     */
    public function update(StaffRequest $request, Staff $staff)
    {

        try {
            $name = trim($request->input(self::NAME));
            $image = $request->file(self::IMAGE);
            $dob = $request->input(self::DOB);
            $start_work = $request->input(self::START_WORK);
            $phone = trim($request->input(self::PHONE));
            $salary = trim($request->input(self::SALARY));
            $bank_account = trim($request->input(self::BANK_ACCOUNT));

            if ($request->has(self::IMAGE)) {
                $image_name = FileUploadService::save($image, "staffs");

                FileUploadService::remove($staff->image, "staffs");
                $staff->image = $image_name;
            }

            $staff->name = $name;
            $staff->dob = $dob;
            $staff->start_work = $start_work;
            $staff->phone = $phone;
            $staff->salary = $salary;
            $staff->bank_account = $bank_account;
            $staff->save();


            return jsend_success(new StaffResource($staff), JsonResponse::HTTP_CREATED);
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
     * @param  Staff  $staff
     * @return \Illuminate\Http\Response
     */
    public function destroy(Staff $staff)
    {
        try {
            $image = $staff->image;
            FileUploadService::remove($image, "staffs");
            $staff->delete();

            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    public function getStaffReport(){
        
        $user = Auth::user();
        $shop_id = $user->shop_id;

        if($shop_id == 0){
                $data=$this->model->with(["services"=>function($query){
               $query->with("service");
                if (request()->start_date && request()->end_date) {
                    $query->whereBetween("created_at", [request()->start_date, request()->end_date]);
                }
            }])->with(["daily_fees"=>function($query){
                
                if (request()->start_date && request()->end_date) {
                    $year=request()->start_date[0].request()->start_date[1].request()->start_date[2].request()->start_date[3];
                    $month=request()->start_date[5].request()->start_date[6];
                    $query->where("month",$month)->where("year",$year);
                   
                }
            }]);
            
       
           
    
            if (request()->id){
                $data=$data->where("id",request()->id);
            }
          
           
    
            $data=$data->get();
            return $data;
        }

        
        $data=$this->model->where("shop_id",request()->user()->shop_id)->with(["services"=>function($query){
           $query->with("service");
            if (request()->start_date && request()->end_date) {
                $query->whereBetween("created_at", [request()->start_date, request()->end_date]);
            }
        }])->with(["daily_fees"=>function($query){
            
            if (request()->start_date && request()->end_date) {
                $year=request()->start_date[0].request()->start_date[1].request()->start_date[2].request()->start_date[3];
                $month=request()->start_date[5].request()->start_date[6];
                $query->where("month",$month)->where("year",$year);
               
            }
        }]);
        
   
       

        if (request()->id){
            $data=$data->where("id",request()->id);
        }
      
       

        $data=$data->get();
        return $data;
    }

}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    const CODE = 'code';
    const NAME = 'name';
    const IMAGE = 'image';
    const BUY_PRICE = 'buy_price';
    const SALE_PRICE = 'sale_price';

    private $model;

    public function __construct(Item $model)
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
        $data = $this->model::all();
        return response()->json(["status" => "success", "data" => ItemResource::collection($data), "total" => count($data)]);
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
            'code' => 'required',
            'name' => 'required',
            'buy_price' => 'required',
            'sale_price' => 'required',
            'image' => 'required',
        ]);
        
        DB::beginTransaction();
        try {
            $code = trim($request->input(self::CODE));
            $name = trim($request->input(self::NAME));
            $image = $request->file(self::IMAGE);
            $buy_price = trim($request->input(self::BUY_PRICE));
            $sale_price = trim($request->input(self::SALE_PRICE));


            $image_name = FileUploadService::save($image, "items");

            $item = new $this->model;
            $item->uuid = Str::uuid()->toString();
            $item->code = $code;
            $item->name = $name;
            $item->image = $image_name;
            $item->buy_price = $buy_price;
            $item->sale_price = $sale_price;
            $item->save();

            DB::commit();
            return jsend_success(new ItemResource($item), JsonResponse::HTTP_CREATED);
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

    /**
     * Display the specified resource.
     *
     * @param  Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        return jsend_success(new ItemResource($item));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Item  $item
     * @return \Illuminate\Http\Response
     */
    public function update(ItemRequest $request, Item $item)
    {

        try {
            $code = trim($request->input(self::CODE));
            $name = trim($request->input(self::NAME));
            $image = $request->file(self::IMAGE);
            $buy_price = trim($request->input(self::BUY_PRICE));
            $sale_price = trim($request->input(self::SALE_PRICE));

            if ($request->has(self::IMAGE)) {
                $image_name = FileUploadService::save($image, "items");

                FileUploadService::remove($item->image, "items");
                $item->image = $image_name;
            }

            $item->code = $code;
            $item->name = $name;
            $item->buy_price = $buy_price;
            $item->sale_price = $sale_price;
            $item->save();


            return jsend_success(new ItemResource($item), JsonResponse::HTTP_CREATED);
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
     * @param  Item  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy(Item $item)
    {

        try {
            $image = $item->image;
            FileUploadService::remove($image, "items");
            $item->delete();

            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    public function download($uuid)
    {
        $item = Item::where('uuid', $uuid)->firstOrFail();
        $storagePath = storage_path('app/items/' . $item->image);
        // return response()->download($pathToFile);

        $mimeType = mime_content_type($storagePath);
        // if( ! \File::exists($storagePath)){
        //     return view('errorpages.404');
        // }
        $headers = array(
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $item->image . '"'
        );
        return \Response::make(file_get_contents($storagePath), 200, $headers);
    }



    public function batchInsert(ItemRequest $request)
    {
        // request()->validate([
        //     'images' => 'required',
        //     'items' => 'required'
        // ]);

        $data = request("items");
        $images = [];
        if (request()->hasFile('images')) {
            foreach (request()->file("images") as $img) { {
                    $image_name = FileUploadService::save($img, "items");
                    array_push($images, $image_name);
                }
            }
        }
        DB::beginTransaction();
        try {
            $saved_data = [];

            foreach ($data as $index => $d) {
                $item = new $this->model;
                $item->uuid = Str::uuid()->toString();
                $item->code = trim($d["code"]);
                $item->name = trim($d["name"]);
                if (count($images)>0){
                         $item->image = $images[$index];
                }
               
                $item->buy_price = trim($d["buy_price"]);
                $item->sale_price = trim($d["sale_price"]);
                $item->save();

                array_push($saved_data, $item);
            }

            DB::commit();
            return jsend_success(ItemResource::collection($saved_data), JsonResponse::HTTP_CREATED);
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

    public function bestItem()
    {
        $items = \App\Models\InvoiceItem::with(['item', 'stock', 'invoice']);

        if(request()->best){
            $items = $items->groupBy("item_id")->select(DB::raw('sum(quantity) as total_qty'), DB::raw('sum(subtotal) as total_subtotal'), 'item_id');    
        }
        
        if (request()->start_date && request()->end_date) {
            $items = $items->whereBetween("created_at", [request()->start_date ." 00:00:00", request()->end_date ." 23:59:59"]);
        }


        $items = $items->get();
        return jsend_success($items, JsonResponse::HTTP_CREATED);
    }
}

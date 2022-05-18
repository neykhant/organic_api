<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function getVoucherCode(){
        $createdAt=\Carbon\Carbon::parse($this->created_at);
        $month=$createdAt->month;
        if ($month<10){
            $month='0'.$month;
        }
        $day=$createdAt->day;
        if ($day<10){
            $day='0'.$day;
        }
        return $createdAt->year.$month.$day.$this->id;
       // $this->id;
    }

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'member' => new MemberResource($this->member),
            'items' => InvoiceItemResource::collection($this->invoiceItems),
            'item_buy_total' => $this->item_buy_total,
            'item_total' => $this->item_total,
            'services' => InvoiceServiceResource::collection($this->invoiceServices),
            'service_total' => $this->service_total,
            'total' => $this->total,
            'discount' => $this->discount,
            'final_total' => $this->final_total,
            'paid' => $this->paid,
            'credit' => $this->credit,
            'payment_method' => $this->payment_method,
            'customer_name' => $this->customer_name == null ? '-' : $this->customer_name,
            'customer_phone_no' => $this->customer_phone_no == null ? '-' : $this->customer_phone_no,
            'shop' => $this->shop,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'voucher_code'=>$this->getVoucherCode(),
        ];
    }
}

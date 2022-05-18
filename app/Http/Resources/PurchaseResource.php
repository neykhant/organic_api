<?php

namespace App\Http\Resources;

use App\Http\Requests\MerchantRequest;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'merchant' => new MerchantResource($this->merchant),
            'whole_total' => $this->whole_total,
            'paid' => $this->paid,
            'credit' => $this->credit,
            'purchase_items' => PurchaseItemResource::collection($this->purchaseItems),
            'purchase_credits' => $this->purchaseCredits,
            'shop_id' => $this->shop_id,
            'shop' => $this->shop,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

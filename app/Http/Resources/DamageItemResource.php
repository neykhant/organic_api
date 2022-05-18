<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DamageItemResource extends JsonResource
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
            'stock' => new StockResource($this->stock),
            'quantity' => $this->quantity,
            'is_sale' => $this->is_sale,
            'shop_id' => $this->shop_id,
            'shop' => $this->shop,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

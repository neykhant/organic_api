<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
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
            'item' => new ItemResource($this->item),
            'quantity' => $this->quantity,
            'shop_id' => $this->shop_id,
            // 'shop' => $this->shop,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'code' => $this->code,
            'category' => $this->category,
            'price' => $this->price,
            'percentage' => $this->percentage,
            'commercial' => $this->commercial,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

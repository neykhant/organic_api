<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
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
            'name' => $this->name,
            'image' => url('/') . "/storage/staffs/{$this->image}",
            'dob' => $this->dob,
            'start_work' => $this->start_work,
            'phone' => $this->phone,
            'salary' => $this->salary,
            'bank_account' => $this->bank_account,
            'shop_id' => $this->shop_id,
            'shop' => $this->shop,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Http\Requests;

class ItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            
            // 'name' => 'required',
            // 'buy_price' => 'required',
            // 'sale_price' => 'required',
            // 'images' => 'required',
            'items.*' => 'required',
            'items.*.code' => 'required|unique:items',
        ];
    }
}

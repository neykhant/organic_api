<?php

namespace App\Http\Requests;

class ItemTransferRequest extends FormRequest
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
            'to_shop_id' => 'required',
            'item_transfers' => 'required'
        ];
    }
}

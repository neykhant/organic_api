<?php

namespace App\Http\Requests;

class PurchaseRequest extends FormRequest
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
            'date' => 'required',
            // 'merchant_id' => 'required',
            'whole_total' => 'required',
            // 'paid' => 'required',
            'purchase_items' => 'required',
        ];
    }
}

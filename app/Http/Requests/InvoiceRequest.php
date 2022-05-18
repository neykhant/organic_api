<?php

namespace App\Http\Requests;

class InvoiceRequest extends FormRequest
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
          //  'item_buy_total' => 'required',
       //     'item_total' => 'required',
         //   'items' => 'required',
            'service_total' => 'required',
         //   'services' => 'required',
            'total' => 'required',
            'discount' => 'required',
            'paid' => 'required',
            'payment_method' => 'required',
        ];
    }
}

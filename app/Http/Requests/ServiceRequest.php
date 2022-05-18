<?php

namespace App\Http\Requests;

class ServiceRequest extends FormRequest
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
            'code' => $this->service ? 'required' : 'required|unique:services',
            'category' => 'required',
            'price' => 'required',
            'percentage' => 'required',
        ];
    }
}

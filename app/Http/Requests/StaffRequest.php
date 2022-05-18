<?php

namespace App\Http\Requests;

class StaffRequest extends FormRequest
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
            'name' => 'required',
            'dob' => 'required',
            'start_work' => 'required',
            'phone' => 'required',
            'salary' => 'required',
            'bank_account' => 'required',
        ];
    }
}

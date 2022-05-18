<?php

namespace App\Http\Requests;

class MemberRequest extends FormRequest
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
            'code'=> $this->member ? 'required' : 'required|unique:members',
            'name'=> 'required',
            'phone'=> 'required',
            'address'=> 'required',
        ];
    }
}

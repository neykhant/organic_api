<?php

namespace App\Http\Requests;

use App\Exceptions\ValidationHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;


abstract class FormRequest extends LaravelFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules();

    public function failedValidation(Validator $validator)
    {
        throw new ValidationHandler($validator);
    }
}

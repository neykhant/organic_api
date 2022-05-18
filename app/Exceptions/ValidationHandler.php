<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;

class ValidationHandler extends Exception
{
    protected $validator;

    protected $code = 422;

    public function __construct(Validator $validator) {
        $this->validator = $validator;
    }

    public function render() {
        // return a json with desired format
        $transformed = [];
        $transformed['status'] = 'fail';
        $transformed['message'] = 'The given data was invalid.';

        foreach ($this->validator->errors()->toArray() as $field => $message) {
            $transformed['data'][$field] = $message[0];
        }

        return response()->json($transformed, JsonResponse::HTTP_BAD_REQUEST);
    }
}

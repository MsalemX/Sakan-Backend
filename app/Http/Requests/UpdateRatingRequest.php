<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRatingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'rate' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|nullable|string',
        ];
    }
}

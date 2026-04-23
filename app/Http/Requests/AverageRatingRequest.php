<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AverageRatingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'housing_id' => 'required|exists:housings,id',
        ];
    }
}

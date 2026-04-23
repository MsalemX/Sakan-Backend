<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'housing_id' => 'required|exists:housings,id',
            'rate' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ];
    }
}

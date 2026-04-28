<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHousingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string',
            'city' => 'sometimes|string',
            'address' => 'sometimes|string',
            'description' => 'sometimes|string',
            'conditions' => 'sometimes|string',
            'base_price' => 'sometimes|numeric',
            'services' => 'nullable|array',
            'services.*.name' => 'required_with:services|string',
            'services.*.extra_price' => 'required_with:services|numeric',
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:5120',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'integer|exists:housing_images,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }
}

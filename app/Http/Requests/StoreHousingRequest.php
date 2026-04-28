<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHousingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'description' => 'required|string',
            'conditions' => 'required|string',
            'base_price' => 'required|numeric',
            'features' => 'nullable|array',
            'capacity' => 'required|integer',
            'remaining_capacity' => 'required|integer',
            'services' => 'nullable|array',
            'services.*.name' => 'required_with:services|string',
            'services.*.extra_price' => 'required_with:services|numeric',
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:5120',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'housing_id' => 'required|exists:housings,id',
            'selected_services' => 'nullable|array',
            'selected_services.*' => 'integer|exists:services,id',
        ];
    }
}

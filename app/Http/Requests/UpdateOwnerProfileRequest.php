<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnerProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:5120',
            'commercial_register' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }
}

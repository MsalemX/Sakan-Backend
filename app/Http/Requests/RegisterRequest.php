<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_name' => 'required|string|exists:roles,name',
            'full_name' => 'required_if:role_name,Student|string|max:255',
            'personal_id_image' => 'required_if:role_name,Student|image|mimes:jpeg,png,jpg,gif|max:2048',
            'father_id_image' => 'required_if:role_name,Student|image|mimes:jpeg,png,jpg,gif|max:2048',
            'university_name' => 'required_if:role_name,Student|string|max:255',
            'major' => 'required_if:role_name,Student|string|max:255',
            'university_card_image' => 'required_if:role_name,Student|image|mimes:jpeg,png,jpg,gif|max:2048',
            'academic_level' => 'required_if:role_name,Student|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone_number' => 'required_if:role_name,Student,Housing Owner|string|max:255',
            'address' => 'required_if:role_name,Student|string|max:255',
            'nationality' => 'required_if:role_name,Student|string|max:255',
            'proof_of_enrollment' => 'required_if:role_name,Student|image|mimes:jpeg,png,jpg,gif|max:2048',
            'commercial_register' => 'required_if:role_name,Housing Owner|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_number' => 'required_if:role_name,Housing Owner|string|max:50',
            'fcm_token' => 'nullable|string',
        ];
    }
}

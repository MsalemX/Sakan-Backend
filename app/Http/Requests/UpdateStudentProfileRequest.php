<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'university_name' => 'required|string|max:255',
            'major' => 'required|string|max:255',
            'academic_level' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'nationality' => 'required|string|max:255',
            'personal_id_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'father_id_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'university_card_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'proof_of_enrollment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ];
    }
}

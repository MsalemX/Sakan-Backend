<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterviewResultRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'interview_result' => 'required|in:passed,failed',
            'notes' => 'nullable|string',
        ];
    }
}

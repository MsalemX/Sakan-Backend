<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterviewRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'request_id' => 'required|exists:booking_requests,id',
            'interview_date' => 'required|date|after:now',
            'notes' => 'nullable|string',
        ];
    }
}

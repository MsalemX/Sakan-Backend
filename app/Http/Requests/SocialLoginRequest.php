<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request التحقق من بيانات Social Login القادمة من Flutter.
 * يتحقق من وجود البيانات الأساسية اللازمة لتسجيل الدخول بـ Google.
 */
class SocialLoginRequest extends FormRequest
{
    /**
     * هل يملك الجميع صلاحية الوصول لهذا الـ endpoint؟
     * نعم، لأنه مفتوح للعموم (تسجيل دخول).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق من البيانات.
     */
    public function rules(): array
    {
        return [
            // idToken الصادر من Google (مطلوب للتحقق الآمن)
            'id_token'   => ['required', 'string'],

            // البيانات الأساسية للمستخدم (ترسل من Flutter بعد نجاح Google Sign-In)
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'google_id'  => ['required', 'string'],
            'avatar'     => ['nullable', 'string'],   // رابط الصورة (قد يكون null)
        ];
    }

    /**
     * رسائل الخطأ المخصصة بالعربية.
     */
    public function messages(): array
    {
        return [
            'id_token.required'  => 'يجب توفير id_token من Google.',
            'name.required'      => 'الاسم مطلوب.',
            'email.required'     => 'البريد الإلكتروني مطلوب.',
            'email.email'        => 'صيغة البريد الإلكتروني غير صحيحة.',
            'google_id.required' => 'معرّف Google مطلوب.',
        ];
    }
}

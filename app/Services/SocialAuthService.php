<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * SocialAuthService - خدمة المصادقة الاجتماعية
 *
 * مسؤولة عن:
 * 1. التحقق من idToken القادم من Google بشكل آمن عبر Google API
 * 2. إيجاد المستخدم أو إنشائه تلقائياً
 * 3. إصدار Sanctum Token للمستخدم
 */
class SocialAuthService
{
    /**
     * التحقق من Google idToken عبر Google TokenInfo API.
     *
     * @param  string $idToken الـ token الصادر من Google في Flutter
     * @return array|null  بيانات المستخدم من Google، أو null إذا فشل التحقق
     */
    public function verifyGoogleToken(string $idToken): ?array
    {
        // نرسل الـ token إلى Google للتحقق من صحته
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        // إذا فشل الطلب أو أعاد خطأ
        if ($response->failed() || $response->json('error_description')) {
            return null;
        }

        $data = $response->json();

        // التأكد من أن الـ token ينتمي لتطبيقنا
        $clientId = config('services.google.client_id');
        if (isset($clientId) && $data['aud'] !== $clientId) {
            return null;
        }

        return $data;
    }

    /**
     * إيجاد مستخدم موجود أو إنشاء حساب جديد بناءً على بيانات Google.
     *
     * منطق البحث:
     * 1. ابحث عبر google_id أولاً (المستخدم سجّل دخولاً بـ Google من قبل)
     * 2. إذا لم يوجد، ابحث عبر email (المستخدم مسجل بالطريقة العادية)
     * 3. إذا لم يوجد، أنشئ حساباً جديداً
     *
     * @param  string      $name     اسم المستخدم
     * @param  string      $email    بريده الإلكتروني
     * @param  string      $googleId معرّفه على Google
     * @param  string|null $avatar   رابط صورته الشخصية
     * @return User
     */
    public function findOrCreateUser(
        string $name,
        string $email,
        string $googleId,
        ?string $avatar = null
    ): User {
        // --- المحاولة 1: البحث عبر google_id ---
        $user = User::where('google_id', $googleId)->first();

        if ($user) {
            // تحديث الاسم والصورة في كل تسجيل دخول (قد يغيرها المستخدم على Google)
            $user->update([
                'name'     => $name,
                'avatar'   => $avatar,
                'provider' => 'google',
            ]);
            return $user;
        }

        // --- المحاولة 2: البحث عبر email (مستخدم موجود بالطريقة العادية) ---
        $user = User::where('email', $email)->first();

        if ($user) {
            // ربط حساب Google بالحساب الموجود
            $user->update([
                'google_id' => $googleId,
                'avatar'    => $user->avatar ?? $avatar, // لا نستبدل الصورة إن كانت موجودة
                'provider'  => $user->provider ?? 'google',
            ]);
            return $user;
        }

        // --- المحاولة 3: إنشاء حساب جديد ---
        // المستخدمون الجدد من Google يحصلون على دور "Student" افتراضياً
        $studentRole = Role::where('name', 'Student')->first();

        $user = User::create([
            'name'      => $name,
            'email'     => $email,
            'google_id' => $googleId,
            'avatar'    => $avatar,
            'provider'  => 'google',
            'password'  => null, // لا كلمة مرور لمستخدمي Google
            'role_id'   => $studentRole?->id,
        ]);

        return $user;
    }

    /**
     * إصدار Sanctum Token للمستخدم.
     *
     * @param  User   $user
     * @return string plainText Token
     */
    public function issueToken(User $user): string
    {
        // حذف التوكنات القديمة لـ Google (اختياري - للحفاظ على نظافة قاعدة البيانات)
        $user->tokens()->where('name', 'google_auth_token')->delete();

        return $user->createToken('google_auth_token')->plainTextToken;
    }
}

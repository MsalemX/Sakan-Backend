<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialLoginRequest;
use App\Services\SocialAuthService;
use Illuminate\Http\JsonResponse;

/**
 * SocialAuthController - متحكم تسجيل الدخول الاجتماعي
 *
 * يتعامل مع طلبات تسجيل الدخول عبر مزودين خارجيين (Google حالياً).
 * يعتمد على SocialAuthService للمنطق الأساسي.
 */
class SocialAuthController extends Controller
{
    public function __construct(
        private readonly SocialAuthService $socialAuthService
    ) {}

    /**
     * تسجيل الدخول عبر Google.
     *
     * الخطوات:
     * 1. التحقق من البيانات الواردة عبر SocialLoginRequest
     * 2. التحقق من idToken مع Google API
     * 3. إيجاد/إنشاء المستخدم
     * 4. إصدار Sanctum Token
     * 5. إعادة Response منظّم
     *
     * @param  SocialLoginRequest $request
     * @return JsonResponse
     */
    public function googleLogin(SocialLoginRequest $request): JsonResponse
    {
        // --- الخطوة 1: التحقق من idToken مع Google ---
        $googleUserData = $this->socialAuthService->verifyGoogleToken(
            $request->id_token
        );

        // إذا فشل التحقق أو كان الـ token غير صالح
        if (! $googleUserData) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق من حساب Google. يرجى المحاولة مرة أخرى.',
            ], 401);
        }

        try {
            // --- الخطوة 2: إيجاد أو إنشاء المستخدم ---
            $user = $this->socialAuthService->findOrCreateUser(
                name:     $request->name,
                email:    $request->email,
                googleId: $request->google_id,
                avatar:   $request->avatar,
            );

            // --- الخطوة 3: إصدار Sanctum Token ---
            $token = $this->socialAuthService->issueToken($user);

            // --- الخطوة 4: إعادة Response منظّم ---
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح عبر Google.',
                'token'   => $token,
                'user'    => $user->load('role', 'student', 'housingOwner'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول: ' . $e->getMessage(),
            ], 500);
        }
    }
}

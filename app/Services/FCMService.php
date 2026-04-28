<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMService
{
    /**
     * إرسال إشعار لمستخدم معين عبر توكن FCM الخاص به.
     */
    public static function sendNotification($token, $title, $body, $data = [])
    {
        if (!$token) {
            return false;
        }

        try {
            $accessToken = self::getAccessToken();
            if (!$accessToken) {
                Log::error('FCM: Failed to get access token');
                return false;
            }

            $projectId = 'housing-app-414bf'; // معرف المشروع من ملف JSON
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $response = Http::withToken($accessToken)->post($url, [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_merge($data, [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('FCM Error Response: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('FCM Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * الحصول على Access Token من Google باستخدام JWT.
     */
    private static function getAccessToken()
    {
        $filePath = storage_path('app/firebase-auth.json');
        if (!file_exists($filePath)) {
            Log::error('FCM: Service account file not found at ' . $filePath);
            return null;
        }

        $authConfig = json_decode(file_get_contents($filePath), true);
        $now = time();

        // إنشاء الـ Header للـ JWT
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        // إنشاء الـ Payload
        $payload = base64_encode(json_encode([
            'iss' => $authConfig['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]));

        // استبدال الرموز لجعلها Base64Url
        $header = str_replace(['+', '/', '='], ['-', '_', ''], $header);
        $payload = str_replace(['+', '/', '='], ['-', '_', ''], $payload);

        $signatureInput = $header . '.' . $payload;
        $signature = '';
        
        // التوقيع باستخدام المفتاح الخاص
        openssl_sign($signatureInput, $signature, $authConfig['private_key'], 'SHA256');
        $signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = $signatureInput . '.' . $signature;

        // طلب التوكن من Google
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        return $response->json('access_token');
    }
}

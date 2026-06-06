<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebasePushService
{
    private static function base64UrlEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function getAccessToken()
    {
        $credPath = storage_path('app/firebase/credentials.json');
        if (!file_exists($credPath)) {
            Log::error("Firebase credentials file not found at: " . $credPath);
            return null;
        }

        try {
            $creds = json_decode(file_get_contents($credPath), true);
            if (!$creds) {
                Log::error("Failed to decode Firebase credentials JSON.");
                return null;
            }

            $privateKey = $creds['private_key'];
            $clientEmail = $creds['client_email'];

            $header = self::base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));

            $now = time();
            $payload = self::base64UrlEncode(json_encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]));

            $signatureInput = $header . '.' . $payload;
            $signature = '';

            if (!openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                Log::error("Failed to sign JWT for Firebase OAuth token.");
                return null;
            }

            $assertion = $signatureInput . '.' . self::base64UrlEncode($signature);

            // Request OAuth access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

            if ($response->failed()) {
                Log::error("Firebase OAuth Token request failed: " . $response->body());
                return null;
            }

            return $response->json('access_token');

        } catch (\Exception $e) {
            Log::error("Error generating Firebase Access Token: " . $e->getMessage());
            return null;
        }
    }

    public static function sendPush(string $token, string $title, string $body, array $data = [])
    {
        $accessToken = self::getAccessToken();
        if (!$accessToken) {
            Log::error("FCM Send skipped: Unable to retrieve OAuth Access Token.");
            return false;
        }

        $credPath = storage_path('app/firebase/credentials.json');
        $creds = json_decode(file_get_contents($credPath), true);
        $projectId = $creds['project_id'] ?? 'pos-mentai';

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // Convert all elements of data array to string, as FCM requires string values in data payload
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string) $key] = (string) $value;
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'webpush' => [
                    'notification' => [
                        'icon' => '/favicon.ico',
                        'badge' => '/favicon.ico',
                    ],
                ],
            ],
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error("FCM send error: " . $response->body());
            return false;
        }

        Log::info("FCM message successfully sent to token: " . substr($token, 0, 15) . "...");
        return true;
    }

    public static function sendToActiveCashiers(string $title, string $body, array $data = [])
    {
        // Get all active users who have an FCM token
        $users = User::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();

        if ($users->isEmpty()) {
            Log::info("FCM broadcast skipped: No users registered with FCM Token.");
            return;
        }

        Log::info("Broadcasting FCM to " . $users->count() . " active devices.");

        foreach ($users as $user) {
            self::sendPush($user->fcm_token, $title, $body, $data);
        }
    }
}

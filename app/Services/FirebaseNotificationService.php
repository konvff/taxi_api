<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected $firebaseUrl;

    public function __construct()
    {
        $projectId = env('FIREBASE_PROJECT_ID', 'default-project-id'); // Ensure env variable is set
        $this->firebaseUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    /**
     * Send Firebase Push Notification.
     *
     * @param  string  $fcmToken
     * @param  string  $title
     * @param  string  $body
     * @param  array  $data
     * @return array|bool
     */
    public function sendNotification($fcmToken, $title, $body, $data = [])
    {
        try {
            $credentialsPath = storage_path('firebase/firebase_credentials.json');

            if (! file_exists($credentialsPath)) {
                Log::error("Firebase credentials file not found: {$credentialsPath}");

                return false;
            }

            putenv("GOOGLE_APPLICATION_CREDENTIALS={$credentialsPath}");

            $client = new Client;
            $client->useApplicationDefaultCredentials();
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'] ?? null;
            if (! $accessToken) {
                Log::error('Failed to obtain Firebase Access Token.');

                return false;
            }

            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_map('strval', $data),
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post($this->firebaseUrl, $payload);

            if ($response->failed()) {
                Log::error('Firebase notification failed: '.$response->body());

                return false;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error sending Firebase notification: '.$e->getMessage());

            return false;
        }
    }
}

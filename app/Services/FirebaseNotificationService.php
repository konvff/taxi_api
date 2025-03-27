<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationService
{
    protected $firebaseUrl = 'https://fcm.googleapis.com/v1/projects/
taxi-app-60181/messages:send';

    public function sendNotification($fcmToken, $title, $body, $data = [])
    {
        $credentialsPath = storage_path('firebase/firebase_credentials.json');

        // Load credentials from JSON file
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$credentialsPath);
        $client = new Client;
        $client->useApplicationDefaultCredentials();
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->firebaseUrl, $payload);

        return $response->json();
    }
}

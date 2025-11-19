<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NotificationController extends Controller
{

    public function sendNotificationV1($token, $title, $body)
    {
        try {
            $jsonPath = storage_path('app/json/coffeeapp-729a2-firebase-adminsdk-fbsvc-405380b8d6.json');
            if (!file_exists($jsonPath)) {
                throw new \Exception("Firebase JSON file not found at: {$jsonPath}");
            }


            $client = new \Google\Client();
            $client->setAuthConfig($jsonPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $tokenResponse = $client->fetchAccessTokenWithAssertion();
            if (isset($tokenResponse['error'])) {
                throw new \Exception("Google Auth Error: " . $tokenResponse['error_description']);
            }

            $accessToken = $tokenResponse['access_token'] ?? null;
            if (!$accessToken) {
                throw new \Exception("Failed to retrieve access token from Firebase.");
            }

            $fcmUrl = 'https://fcm.googleapis.com/v1/projects/coffeeapp-729a2/messages:send';
            $response = Http::withToken($accessToken)
                ->timeout(20)
                ->post($fcmUrl, [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                ]);

            if ($response->failed()) {
                $errorBody = $response->json();
                throw new \Exception("Firebase Error: " . json_encode($errorBody));
            }

            return [
                'status' => 'success',
                'response' => $response->json(),
            ];


        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return response()->json([
                'error' => 'Network connection error: ' . $e->getMessage(),
            ], 500);

        } catch (\Google\Service\Exception $e) {
            return response()->json([
                'error' => 'Google API Exception: ' . $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }
}

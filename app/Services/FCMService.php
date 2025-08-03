<?php

namespace App\Services;

use App\Models\Notification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FCMNotification;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(config('services.firebase.credentials'))
                ->withProjectId(config('services.firebase.project_id'));
            
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    /**
     * Send notification via FCM and save to database
     *
     * @param string $userId
     * @param string $fcmToken
     * @param string $title
     * @param string $message
     * @param array $data
     * @param string $actionType
     * @param array $actionData
     * @return bool
     */
    public function sendNotification($userId, $fcmToken, $title, $message, $data = [], $actionType = null, $actionData = [])
    {
        try {
            // Save notification to database first
            $notification = Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'read_at' => null,
                'action_type' => $actionType,
                'action_data' => $actionData,
            ]);

            // Send FCM notification if token exists and messaging is available
            if ($fcmToken && $this->messaging) {
                $fcmNotification = FCMNotification::create($title, $message);
                
                $cloudMessage = CloudMessage::withTarget('token', $fcmToken)
                    ->withNotification($fcmNotification);

                // Add custom data if provided
                if (!empty($data)) {
                    $cloudMessage = $cloudMessage->withData($data);
                }

                // Add action data for navigation
                if ($actionType && !empty($actionData)) {
                    $navigationData = [
                        'action_type' => $actionType,
                        'action_data' => json_encode($actionData)
                    ];
                    $cloudMessage = $cloudMessage->withData(array_merge($data, $navigationData));
                }

                $this->messaging->send($cloudMessage);
                
                Log::info('FCM notification sent successfully', [
                    'user_id' => $userId,
                    'title' => $title,
                    'fcm_token' => substr($fcmToken, 0, 10) . '...'
                ]);
            } else {
                Log::warning('FCM notification not sent - missing token or messaging service', [
                    'user_id' => $userId,
                    'has_token' => !empty($fcmToken),
                    'has_messaging' => !is_null($this->messaging)
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send FCM notification: ' . $e->getMessage(), [
                'user_id' => $userId,
                'title' => $title,
                'error' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Send notification to multiple users
     *
     * @param array $users Array of ['user_id' => '', 'fcm_token' => '']
     * @param string $title
     * @param string $message
     * @param array $data
     * @param string $actionType
     * @param array $actionData
     * @return array
     */
    public function sendBulkNotification($users, $title, $message, $data = [], $actionType = null, $actionData = [])
    {
        $results = [];
        
        foreach ($users as $user) {
            $result = $this->sendNotification(
                $user['user_id'], 
                $user['fcm_token'], 
                $title, 
                $message, 
                $data,
                $actionType,
                $actionData
            );
            
            $results[] = [
                'user_id' => $user['user_id'],
                'success' => $result
            ];
        }

        return $results;
    }
}

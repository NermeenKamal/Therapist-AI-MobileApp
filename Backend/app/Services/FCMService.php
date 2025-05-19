<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FCMService
{
    /**
     * Send notification to a specific user by FCM token
     * 
     * @param string $token FCM token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data
     * @return array Response from Firebase
     */
    public function sendToUser(string $token, string $title, string $body, array $data = []): array
    {
        $messaging = Firebase::messaging();

        $notification = Notification::create($title, $body);
        
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withData($data);
        
        try {
            $response = $messaging->send($message);
            return [
                'success' => true,
                'message' => 'Notification sent successfully',
                'result' => $response,
            ];
        } catch (\Exception $e) {
            logger()->error('FCM Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to multiple users by FCM tokens
     * 
     * @param array $tokens Array of FCM tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data
     * @return array Response from Firebase
     */
    public function sendToMultipleUsers(array $tokens, string $title, string $body, array $data = []): array
    {
        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => 'No tokens provided'
            ];
        }

        $messaging = Firebase::messaging();
        
        $notification = Notification::create($title, $body);
        
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data);
        
        try {
            $response = $messaging->sendMulticast($message, $tokens);
            
            return [
                'success' => true,
                'message' => 'Notifications sent successfully',
                'result' => [
                    'success_count' => $response->successes()->count(),
                    'failure_count' => $response->failures()->count(),
                ],
            ];
        } catch (\Exception $e) {
            logger()->error('FCM Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send a topic-based notification
     * 
     * @param string $topic Topic name
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data
     * @return array Response from Firebase
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): array
    {
        $messaging = Firebase::messaging();
        
        $notification = Notification::create($title, $body);
        
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification($notification)
            ->withData($data);
        
        try {
            $response = $messaging->send($message);
            
            return [
                'success' => true,
                'message' => 'Topic notification sent successfully',
                'result' => $response,
            ];
        } catch (\Exception $e) {
            logger()->error('FCM Topic Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Subscribe tokens to a topic
     * 
     * @param array $tokens Array of FCM tokens
     * @param string $topic Topic name
     * @return array Response from Firebase
     */
    public function subscribeToTopic(array $tokens, string $topic): array
    {
        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => 'No tokens provided'
            ];
        }

        $messaging = Firebase::messaging();
        
        try {
            $response = $messaging->subscribeToTopic($topic, $tokens);
            
            return [
                'success' => true,
                'message' => 'Subscribed to topic successfully',
                'result' => [
                    'success_count' => $response->successes()->count(),
                    'failure_count' => $response->failures()->count(),
                ],
            ];
        } catch (\Exception $e) {
            logger()->error('FCM Topic Subscription Error: ' . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage()
            ];
        }
    }
}

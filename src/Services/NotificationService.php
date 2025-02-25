<?php

use Models\User;
use Classes\Storage\JsonStorage;
class NotificationService {

    private $storage;
    private $user;
    private $defaultChannel = 'email';

    public function __construct(User $user) {
        $this->storage = new JsonStorage('notifications.json');
        $this->user = $user;
    }

    public function send($userId, $message, $type = 'info', $channel = null) {
        $user = $this->user->findById($userId);
        if (!$user) {
            error_log("[NotificationService] User not found: $userId");
            return false;
        }

        $notification = [
            'user_id' => $userId,
            'message' => $message,
            'type' => $type,
            'channel' => $channel ?? $this->defaultChannel,
            'read' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Store notification
        $notificationId = $this->storage->insert($notification);
        
        // Send through appropriate channel
        $sent = $this->sendThroughChannel($notification, $user);
        
        if ($sent) {
            error_log("[NotificationService] Notification sent to user $userId through {$notification['channel']}");
            return $notificationId;
        }

        error_log("[NotificationService] Failed to send notification to user $userId");
        return false;
    }

    public function markAsRead($notificationId) {
        return $this->storage->update($notificationId, ['read' => true]);
    }

    public function getUnreadNotifications($userId) {
        $notifications = $this->storage->findByField('user_id', $userId);
        return array_filter($notifications, function($notification) {
            return !$notification['read'];
        });
    }

    private function sendThroughChannel($notification, $user) {
        switch ($notification['channel']) {
            case 'email':
                return $this->sendEmail($notification, $user);
            case 'in-app':
                return $this->sendInApp($notification, $user);
            default:
                error_log("[NotificationService] Unknown channel: {$notification['channel']}");
                return false;
        }
    }

    private function sendEmail($notification, $user) {
        // In a real application, this would send an actual email
        error_log("[NotificationService] Sending email to: {$user['email']}");
        error_log("[NotificationService] Subject: {$notification['type']} notification");
        error_log("[NotificationService] Message: {$notification['message']}");
        return true;
    }

    private function sendInApp($notification, $user) {
        // In a real application, this might push to a websocket or store for polling
        error_log("[NotificationService] Sending in-app notification to user: {$user['id']}");
        return true;
    }
} 
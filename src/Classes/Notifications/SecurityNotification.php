<?php

namespace Classes\Notifications;
use Classes\Storage\JsonStorage;
use Models\User;
class SecurityNotification {
    private $storage;
    private $user;
    
    // Notification types
    const NEW_LOGIN = 'new_login';
    const PASSWORD_CHANGED = 'password_changed';
    const FAILED_LOGIN_ATTEMPT = 'failed_login_attempt';
    const TWO_FA_DISABLED = '2fa_disabled';
    const SECURITY_SETTINGS_CHANGED = 'security_settings_changed';
    
    public function __construct() {
        $this->storage = new JsonStorage('notifications.json');
        $this->user = new User();
    }
    
    public function create($userId, $type, $data = []) {
        $notification = [
            'id' => uniqid('notif_'),
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'created_at' => date('Y-m-d H:i:s'),
            'read' => false,
            'priority' => $this->getPriority($type)
        ];
        
        $data = $this->storage->data;
        $data['items'][] = $notification;
        $this->storage->data = $data;
        $this->storage->save();
        
        error_log("[Notification] Created: $type for user: $userId");
        return $notification;
    }

    public function getUnread($userId) {
        $notifications = [];
        foreach ($this->storage->data['items'] ?? [] as $notification) {
            if ($notification['user_id'] === $userId && !$notification['read']) {
                $notifications[] = $notification;
            }
        }
        return $notifications;
    }

    public function markAsRead($notificationId, $userId) {
        $data = $this->storage->data;
        foreach ($data['items'] as $index => $notification) {
            if ($notification['id'] === $notificationId && $notification['user_id'] === $userId) {
                $data['items'][$index]['read'] = true;
                $data['items'][$index]['read_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        $this->storage->data = $data;
        $this->storage->save();
    }

    public function getNotificationMessage($notification) {
        $time = date('M j, Y g:i A', strtotime($notification['created_at']));
        
        switch ($notification['type']) {
            case self::NEW_LOGIN:
                return "New login from {$notification['data']['ip_address']} using {$notification['data']['browser']} at $time";
                
            case self::PASSWORD_CHANGED:
                return "Your password was changed at $time";
                
            case self::FAILED_LOGIN_ATTEMPT:
                return "Failed login attempt from {$notification['data']['ip_address']} at $time";
                
            case self::TWO_FA_DISABLED:
                return "Two-factor authentication was disabled at $time";
                
            case self::SECURITY_SETTINGS_CHANGED:
                return "Security settings were modified at $time";
                
            default:
                return "Security event occurred at $time";
        }
    }

    private function getPriority($type) {
        switch ($type) {
            case self::FAILED_LOGIN_ATTEMPT:
            case self::TWO_FA_DISABLED:
                return 'high';
                
            case self::PASSWORD_CHANGED:
            case self::NEW_LOGIN:
                return 'medium';
                
            default:
                return 'low';
        }
    }

    public function cleanup() {
        // Remove notifications older than 30 days
        $data = $this->storage->data;
        $thirtyDaysAgo = strtotime('-30 days');
        
        $data['items'] = array_filter($data['items'], function($notification) use ($thirtyDaysAgo) {
            return strtotime($notification['created_at']) > $thirtyDaysAgo;
        });
        
        $this->storage->data = $data;
        $this->storage->save();
    }
} 
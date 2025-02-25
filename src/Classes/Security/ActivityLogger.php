<?php

namespace Classes\Security;

use Classes\Storage\JsonStorage;

class ActivityLogger {
    private $storage;
    
    // Activity types
    const LOGIN = 'login';
    const LOGOUT = 'logout';
    const PASSWORD_CHANGE = 'password_change';
    const PROFILE_UPDATE = 'profile_update';
    const SECURITY_SETTING = 'security_setting';
    const TWO_FACTOR = 'two_factor';
    
    public function __construct() {
        $this->storage = new JsonStorage('user_activities.json');
    }
    
    public function log($userId, $type, $details = []) {
        // Convert array details to JSON string for logging
        $detailsStr = is_array($details) ? json_encode($details) : (string)$details;

        $activity = [
            'id' => uniqid('act_'),
            'user_id' => $userId,
            'type' => $type,
            'details' => $detailsStr,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $data = $this->storage->data;
        $data['items'][] = $activity;
        $this->storage->data = $data;
        $this->storage->save();
        
        error_log("[Activity] Logged: " . $detailsStr . " for user: " . $type);
        return $activity;
    }
    
    public function getUserActivities($userId, $limit = 50) {
        $activities = [];
        $count = 0;
        
        foreach (array_reverse($this->storage->data['items'] ?? []) as $activity) {
            if ($activity['user_id'] === $userId) {
                $activities[] = $activity;
                $count++;
                
                if ($count >= $limit) {
                    break;
                }
            }
        }
        
        return $activities;
    }
    
    public function getActivityDescription($activity) {
        $time = date('M j, Y g:i A', strtotime($activity['created_at']));
        
        switch ($activity['type']) {
            case self::LOGIN:
                return "Logged in at $time";
                
            case self::LOGOUT:
                return "Logged out at $time";
                
            case self::PASSWORD_CHANGE:
                return "Changed password at $time";
                
            case self::PROFILE_UPDATE:
                return "Updated profile information at $time";
                
            case self::SECURITY_SETTING:
                return "Modified security settings at $time";
                
            case self::TWO_FACTOR:
                $action = $activity['details']['action'] ?? 'modified';
                return "Two-factor authentication $action at $time";
                
            default:
                return "Unknown activity at $time";
        }
    }
}
<?php

use Classes\Storage\JsonStorage;
class UserActivityLogger {
    private $storage;

    public function __construct() {
        $this->storage = new JsonStorage('user_activities.json');
    }

    public function log($userId, $activity, $details = null) {
        $activityData = [
            'user_id' => $userId,
            'activity' => $activity,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->storage->insert($activityData);
        error_log("[UserActivityLogger] Logged activity for user ID: $userId - $activity");
    }

    public function getActivities($userId) {
        return $this->storage->findByField('user_id', $userId);
    }
} 
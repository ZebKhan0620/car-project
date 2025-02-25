<?php

namespace Classes\Security;

class SecurityNotification {
    public const NEW_LOGIN = 'new_login';
    public const SECURITY_SETTINGS_CHANGED = 'security_settings_changed';

    private $storage;

    public function __construct() {
        $this->storage = new \Classes\Storage\JsonStorage('security_notifications.json');
    }

    public function create($userId, $type, $data = []) {
        $notification = [
            'id' => uniqid('notif_'),
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'created_at' => date('Y-m-d H:i:s'),
            'read' => false
        ];

        $storageData = $this->storage->data;
        $storageData['items'][] = $notification;
        $this->storage->data = $storageData;
        $this->storage->save();

        return $notification;
    }
}
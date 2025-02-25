<?php

class SecurityLogger {
    private $logFile;
    private $storage;

    public function __construct() {
        $this->storage = new JsonStorage('security_logs.json');
    }

    /**
     * Log a security event
     * 
     * @param string $event_type The type of security event
     * @param array $data Additional event data
     * @param string $user_id User ID if applicable
     * @return bool
     */
    public function log($event_type, array $data = [], $user_id = null) {
        try {
            $logEntry = [
                'id' => uniqid(),
                'timestamp' => date('Y-m-d H:i:s'),
                'event_type' => $event_type,
                'user_id' => $user_id,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'data' => $data
            ];

            error_log("[Security] " . json_encode($logEntry));

            // Get current logs and append new entry
            $currentData = $this->storage->load();
            $currentData['items'] = $currentData['items'] ?? [];
            $currentData['items'][] = $logEntry;
            
            // Save all logs
            return $this->storage->save();

        } catch (Exception $e) {
            error_log("[SecurityLogger] Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get security logs with optional filtering
     */
    public function getLogs($filters = []) {
        try {
            $logs = $this->storage->load()['items'] ?? [];

            if (!empty($filters)) {
                $logs = array_filter($logs, function($log) use ($filters) {
                    foreach ($filters as $key => $value) {
                        if (!isset($log[$key]) || $log[$key] !== $value) {
                            return false;
                        }
                    }
                    return true;
                });
            }

            return array_reverse($logs); // Most recent first

        } catch (Exception $e) {
            error_log("[SecurityLogger] Error getting logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get logs for a specific user
     */
    public function getUserLogs($user_id) {
        return $this->getLogs(['user_id' => $user_id]);
    }

    /**
     * Clear old logs (older than X days)
     */
    public function clearOldLogs($days = 30) {
        try {
            $logs = $this->storage->load()['items'] ?? [];
            $cutoff = strtotime("-$days days");

            $filtered = array_filter($logs, function($log) use ($cutoff) {
                return strtotime($log['timestamp']) > $cutoff;
            });

            $this->storage->data['items'] = array_values($filtered);
            return $this->storage->save();

        } catch (Exception $e) {
            error_log("[SecurityLogger] Error clearing old logs: " . $e->getMessage());
            return false;
        }
    }
} 
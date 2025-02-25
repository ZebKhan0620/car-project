<?php

use Classes\Storage\JsonStorage;
class SecurityService {
    private $storage;
    private $maxFailedAttempts = 5;
    private $blockDuration = 3600; // 1 hour in seconds
    private $suspiciousThreshold = 3;

    public function __construct() {
        $this->storage = new JsonStorage('security_logs.json');
    }

    public function logActivity($userId, $action, $ip, $userAgent, $status = 'success') {
        // First check if IP is blocked, but only for failed attempts
        if ($status === 'failed' && $this->isIPBlocked($ip)) {
            error_log("[Security] Blocked IP attempting action: $ip");
            return ['success' => false, 'message' => 'IP address is blocked'];
        }

        $activity = [
            'user_id' => $userId,
            'action' => $action,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'status' => $status,
            'timestamp' => time(),
            'device_fingerprint' => $this->generateDeviceFingerprint($ip, $userAgent)
        ];

        $insertResult = $this->storage->insert($activity);
        if (!$insertResult) {
            error_log("[Security] Failed to log activity");
            return ['success' => false, 'message' => 'Failed to log activity'];
        }

        error_log("[Security] Activity logged: " . json_encode($activity));

        // Only check for suspicious activity on failed attempts
        if ($status === 'failed' && $this->isSuspiciousActivity($userId, $ip, $userAgent)) {
            error_log("[Security] Suspicious activity detected for user: $userId");
            return ['success' => false, 'message' => 'Suspicious activity detected'];
        }

        return ['success' => true];
    }

    public function isIPBlocked($ip) {
        $failedAttempts = $this->getFailedAttempts($ip);
        $recentFailures = count($failedAttempts);
        
        // If no failures or less than max attempts, IP is not blocked
        if ($recentFailures < $this->maxFailedAttempts) {
            return false;
        }

        // Check the timestamp of the oldest attempt within the block window
        $lastAttempt = end($failedAttempts);
        if (!$lastAttempt) {
            return false;
        }

        $timeSinceLastAttempt = time() - $lastAttempt['timestamp'];
        if ($timeSinceLastAttempt < $this->blockDuration) {
            error_log("[Security] IP blocked: $ip (Failed attempts: $recentFailures, Time remaining: " . 
                     ($this->blockDuration - $timeSinceLastAttempt) . " seconds)");
            return true;
        }

        return false;
    }

    public function getDeviceHistory($userId) {
        $activities = $this->storage->findByField('user_id', $userId);
        $devices = [];

        foreach ($activities as $activity) {
            $fingerprint = $activity['device_fingerprint'];
            if (!isset($devices[$fingerprint])) {
                $devices[$fingerprint] = [
                    'first_seen' => $activity['timestamp'],
                    'last_seen' => $activity['timestamp'],
                    'ip' => $activity['ip'],
                    'user_agent' => $activity['user_agent'],
                    'activity_count' => 1
                ];
            } else {
                $devices[$fingerprint]['last_seen'] = $activity['timestamp'];
                $devices[$fingerprint]['activity_count']++;
            }
        }

        return $devices;
    }

    public function isNewDevice($userId, $ip, $userAgent) {
        $fingerprint = $this->generateDeviceFingerprint($ip, $userAgent);
        $devices = $this->getDeviceHistory($userId);
        return !isset($devices[$fingerprint]);
    }

    public function getLocationInfo($ip) {
        // In a real application, you would use a geolocation service
        // For demo purposes, we'll return mock data
        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'isp' => 'Unknown'
        ];
    }

    private function isSuspiciousActivity($userId, $ip, $userAgent) {
        $recentActivities = $this->getRecentActivities($userId);
        $suspiciousPatterns = 0;

        // Check for multiple failed attempts
        $failedAttempts = array_filter($recentActivities, function($activity) {
            return $activity['status'] === 'failed';
        });
        
        if (count($failedAttempts) >= $this->suspiciousThreshold) {
            $suspiciousPatterns++;
            error_log("[Security] Multiple failed attempts detected for user: $userId");
        }

        // Check for multiple devices
        $uniqueDevices = array_unique(array_map(function($activity) {
            return $activity['device_fingerprint'];
        }, $recentActivities));
        
        if (count($uniqueDevices) >= $this->suspiciousThreshold) {
            $suspiciousPatterns++;
            error_log("[Security] Multiple devices detected for user: $userId");
        }

        // Check for multiple IPs
        $uniqueIPs = array_unique(array_map(function($activity) {
            return $activity['ip'];
        }, $recentActivities));
        
        if (count($uniqueIPs) >= $this->suspiciousThreshold) {
            $suspiciousPatterns++;
            error_log("[Security] Multiple IPs detected for user: $userId");
        }

        return $suspiciousPatterns >= 2;
    }

    private function getFailedAttempts($ip) {
        $activities = $this->storage->findByField('ip', $ip);
        $threshold = time() - $this->blockDuration;
        
        return array_filter($activities, function($activity) use ($threshold) {
            return isset($activity['status']) && 
                   isset($activity['timestamp']) &&
                   $activity['status'] === 'failed' && 
                   $activity['timestamp'] > $threshold;
        });
    }

    private function getRecentActivities($userId, $timeframe = 3600) {
        $activities = $this->storage->findByField('user_id', $userId);
        return array_filter($activities, function($activity) use ($timeframe) {
            return time() - $activity['timestamp'] < $timeframe;
        });
    }

    private function generateDeviceFingerprint($ip, $userAgent) {
        // In a real application, you would use more sophisticated fingerprinting
        // including screen resolution, installed plugins, etc.
        $data = $ip . '|' . $userAgent;
        return hash('sha256', $data);
    }

    public function clearOldLogs($days = 30) {
        $threshold = time() - ($days * 24 * 60 * 60);
        $activities = $this->storage->findByField('timestamp', null, function($record) use ($threshold) {
            return isset($record['timestamp']) && $record['timestamp'] < $threshold;
        });
        
        foreach ($activities as $activity) {
            $this->storage->delete($activity['id']);
        }
        
        error_log("[Security] Old logs cleared");
    }
} 
<?php

namespace Services;

require_once __DIR__ . '/../Classes/Auth/Session.php';
require_once __DIR__ . '/../Classes/Storage/JsonStorage.php';
require_once __DIR__ . '/../Classes/Security/SecurityNotification.php';
require_once __DIR__ . '/../Classes/Security/ActivityLogger.php';
require_once __DIR__ . '/../Models/User.php';

use Classes\Auth\Session;
use Classes\Storage\JsonStorage;
use Classes\Security\SecurityNotification;
use Classes\Security\ActivityLogger;
use Models\User;

class SessionService {
    private $storage;
    private $session;
    private $timeoutMinutes = 30; // Increase timeout
    private $expiryHours = 24;    // Increase expiry

    public function __construct() {
        $this->storage = new JsonStorage('sessions.json');
        $this->session = Session::getInstance();
    }

    public function createSession($userId) {
        $sessionId = session_id();
        $data = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'last_activity' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];

        error_log("[Session] Creating new session for user: $userId");
        $result = $this->storage->insert($data);
        
        if (!$result) {
            error_log("[Session] Failed to create session record");
            throw new \Exception('Failed to create session record');
        }

        // Create new login notification
        $notifications = new SecurityNotification();
        $notifications->create($userId, SecurityNotification::NEW_LOGIN, [
            'ip_address' => $data['ip_address'],
            'browser' => $data['user_agent']
        ]);

        // Log the login activity
        $activityLogger = new ActivityLogger();
        $activityLogger->log($userId, ActivityLogger::LOGIN, [
            'ip_address' => $data['ip_address'],
            'browser' => $data['user_agent']
        ]);

        error_log("[Session] Created new session: $sessionId for user: $userId");
        return $sessionId;
    }

    public function isSessionValid($sessionId) {
        $data = $this->storage->data;
        if (!isset($data['items'][$sessionId])) {
            error_log("[Session] Session not found: $sessionId");
            return false;
        }

        $session = $data['items'][$sessionId];
        $lastActivity = strtotime($session['last_activity']);
        $now = time();

        // Check timeout and expiry
        if (($now - $lastActivity) > ($this->timeoutMinutes * 60)) {
            error_log("[Session] Session timed out: $sessionId");
            $this->terminateSession($sessionId, 'Session expired');
            return false;
        }

        // Update last activity
        $data['items'][$sessionId]['last_activity'] = date('Y-m-d H:i:s');
        $this->storage->data = $data;
        $this->storage->save();

        return true;
    }

    public function terminateSession($sessionId, $reason = '') {
        $data = $this->storage->data;
        if (isset($data['items'][$sessionId])) {
            $userId = $data['items'][$sessionId]['user_id'];
            
            // Log security event
            $user = new User();
            $user->logSecurityEvent('session_terminated', [
                'reason' => $reason,
                'session_id' => $sessionId
            ], $userId);

            // Log logout activity only if reason is user logout
            if ($reason === 'User logout') {
                $activityLogger = new ActivityLogger();
                $activityLogger->log($userId, ActivityLogger::LOGOUT, [
                    'reason' => $reason,
                    'session_id' => $sessionId
                ]);
            }

            // Remove session
            unset($data['items'][$sessionId]);
            $this->storage->data = $data;
            $this->storage->save();
            
            error_log("[Session] Terminated session: $sessionId, Reason: $reason");
        }
    }

    public function requireAuth() {
        if (!$this->session->get('user_id')) {
            header('Location: /car-project/public/login.php?message=Please login to continue&type=warning');
            exit;
        }
    }

    public function getActiveSessions($userId = null) {
        $data = $this->storage->data;
        $activeSessions = [];

        foreach ($data['items'] ?? [] as $sessionId => $session) {
            // If userId is provided, only get sessions for that user
            if ($userId && $session['user_id'] !== $userId) {
                continue;
            }

            // Check if session is still valid
            if ($this->isSessionValid($sessionId)) {
                $activeSessions[$sessionId] = $session;
            }
        }

        error_log("[Session] Found " . count($activeSessions) . " active sessions" . 
                 ($userId ? " for user $userId" : ""));
        
        return $activeSessions;
    }

    public function terminateOtherSessions($currentSessionId, $userId) {
        $data = $this->storage->data;
        $terminatedCount = 0;

        // First collect sessions to terminate
        $sessionsToTerminate = [];
        foreach ($data['items'] ?? [] as $sessionId => $session) {
            if ($sessionId !== $currentSessionId && $session['user_id'] === $userId) {
                $sessionsToTerminate[] = $sessionId;
            }
        }

        // Then terminate each session
        foreach ($sessionsToTerminate as $sessionId) {
            $this->terminateSession($sessionId, 'Terminated by user');
            $terminatedCount++;
        }

        if ($terminatedCount > 0) {
            // Log the action
            $user = new User();
            $user->logSecurityEvent('sessions_terminated', [
                'count' => $terminatedCount,
                'current_session' => $currentSessionId
            ], $userId);

            // Clear current session
            $this->session->destroy();
            session_start(); // Start new session for message

            // Redirect with message
            header('Location: /car-project/public/login.php?message=All other sessions terminated. Please login again&type=success');
            exit;
        }

        return $terminatedCount;
    }
}
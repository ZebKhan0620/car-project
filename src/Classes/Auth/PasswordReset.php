<?php

namespace Classes\Auth;

use Classes\Storage\JsonStorage;
use Models\User;
use Classes\Notification\SecurityNotification;
use Classes\Log\ActivityLogger;
use Exception;

class PasswordReset {
    private $storage;
    private $user;
    private $tokenExpiry = 3600; // 1 hour

    public function __construct() {
        $this->storage = new JsonStorage('password_resets.json');
        $this->user = new User();
    }

    public function createToken($email) {
        $user = $this->user->findByEmail($email);
        if (!$user) {
            error_log("[PasswordReset] No user found for email: $email");
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + $this->tokenExpiry);

        $data = $this->storage->data;
        $data['items'][] = [
            'email' => $email,
            'token' => $token,
            'expires' => $expires,  // Keep consistent field naming
            'created_at' => date('Y-m-d H:i:s'),
            'used' => false
        ];

        $this->storage->data = $data;
        $this->storage->save();

        error_log("[PasswordReset] Created token for email: $email");
        return $token;
    }

    public function validateToken($token) {
        try {
            // Check if token exists and is not expired
            $reset = $this->storage->findByField('token', $token);
            if (!$reset) {
                error_log("[PasswordReset] Token not found: " . $token);
                return false;
            }

            // Check expiration
            if (strtotime($reset['expires']) < time()) {
                error_log("[PasswordReset] Token expired: " . $token);
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log("[PasswordReset] Validation error: " . $e->getMessage());
            return false;
        }
    }

    public function verifyToken($token) {
        error_log("[PasswordReset] Verifying token: " . substr($token, 0, 8) . "...");
        
        // Get all reset requests
        $data = $this->storage->load();
        if (!isset($data['items']) || empty($data['items'])) {
            error_log("[PasswordReset] No reset requests found");
            return false;
        }

        // Find matching token
        $resetRequest = null;
        foreach ($data['items'] as $item) {
            if ($item['token'] === $token && !$item['used']) {
                $resetRequest = $item;
                break;
            }
        }

        if (!$resetRequest) {
            error_log("[PasswordReset] Token not found or already used");
            return false;
        }

        // Check expiration
        if (strtotime($resetRequest['expires']) < time()) {
            error_log("[PasswordReset] Token expired for email: " . $resetRequest['email']);
            return false;
        }

        error_log("[PasswordReset] Token valid for email: " . $resetRequest['email']);
        return $resetRequest;
    }

    public function resetPassword($token, $newPassword) {
        try {
            error_log("[PasswordReset] Starting password reset process for token: " . substr($token, 0, 8));
            
            // Find the reset request
            $resetRequest = $this->verifyToken($token);
            if (!$resetRequest) {
                error_log("[PasswordReset] Invalid or expired token");
                return false;
            }

            // Find user and update password
            $user = $this->user->findByEmail($resetRequest['email']);
            if (!$user) {
                error_log("[PasswordReset] User not found: " . $resetRequest['email']);
                return false;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $this->user->update($user['id'], ['password' => $hashedPassword]);

            if ($result['success']) {
                // Mark token as used
                $this->markTokenAsUsed($token);
                error_log("[PasswordReset] Successfully reset password for user: " . $user['id']);
                return true;
            }

            error_log("[PasswordReset] Failed to update password for user: " . $user['id']);
            return false;

        } catch (Exception $e) {
            error_log("[PasswordReset] Error during reset: " . $e->getMessage());
            return false;
        }
    }

    private function markTokenAsUsed($token) {
        $data = $this->storage->data;
        foreach ($data['items'] as $index => $reset) {
            if ($reset['token'] === $token) {
                $data['items'][$index]['used'] = true;
                break;
            }
        }
        $this->storage->data = $data;
        $this->storage->save();
        error_log("[PasswordReset] Token marked as used: " . substr($token, 0, 8));
    }

    private function invalidateToken($token) {
        $data = $this->storage->data;
        foreach ($data['items'] as $key => $item) {
            if ($item['token'] === $token) {
                unset($data['items'][$key]);
                break;
            }
        }
        $this->storage->data = $data;
        $this->storage->save();
    }

    public function getResetLink($token) {
        $baseUrl = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        return "http://" . $baseUrl . "/car-project/public/reset-password.php?token=" . $token;
    }
}

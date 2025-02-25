<?php

use Models\User;
class EmailVerification {
    private $user;
    private $tokenLength = 32;
    private $tokenExpiry = 86400; // 24 hours in seconds

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function generateVerificationToken($userId) {
        error_log("[EmailVerification] Generating verification token for user: " . $userId);
        
        // Generate a secure random token
        $token = bin2hex(random_bytes($this->tokenLength));
        
        // Update user with verification data
        $verificationData = [
            'verification_token' => $token,
            'email_verified_at' => null,
            'is_verified' => false
        ];
        
        error_log("[EmailVerification] Updating user with verification data: " . json_encode($verificationData));
        
        if ($this->user->update($userId, $verificationData)) {
            return $token;
        }
        
        return false;
    }

    public function sendVerificationEmail($userId, $userEmail) {
        error_log("[EmailVerification] Attempting to send verification email. User ID: $userId, Email: $userEmail");
        
        if (empty($userId) || empty($userEmail)) {
            error_log("[EmailVerification] Invalid user data provided");
            return false;
        }

        try {
            $token = $this->generateVerificationToken($userId);
            if (!$token) {
                throw new Exception("Failed to generate verification token");
            }

            // In development, just log the verification link
            $verificationLink = "http://localhost/verify-email.php?token=" . $token;
            error_log("[EmailVerification] Verification link generated: " . $verificationLink);
            
            return true;
        } catch (Exception $e) {
            error_log("[EmailVerification] Error: " . $e->getMessage());
            return false;
        }
    }

    public function verifyEmail($token) {
        error_log("[EmailVerification] Verifying token: " . $token);
        
        // Find user by verification token
        $user = $this->findUserByToken($token);
        if (!$user) {
            error_log("[EmailVerification] Invalid verification token");
            return ['success' => false, 'error' => 'Invalid verification token'];
        }

        // Update user verification status
        $updateData = [
            'is_verified' => true,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null
        ];

        if ($this->user->update($user['id'], $updateData)) {
            error_log("[EmailVerification] Email verified successfully for user: " . $user['id']);
            return ['success' => true, 'message' => 'Email verified successfully'];
        }

        error_log("[EmailVerification] Failed to verify email");
        return ['success' => false, 'error' => 'Failed to verify email'];
    }

    private function findUserByToken($token) {
        // In a real application, you might want to check token expiry here
        return $this->user->findByField('verification_token', $token);
    }

    private function generateVerificationLink($token) {
        $baseUrl = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        return "http://" . $baseUrl . "/verify-email.php?token=" . $token;
    }
}

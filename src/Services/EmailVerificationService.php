<?php

use Models\User;
class EmailVerificationService {
    private $user;
    private $mailer;
    private $config;
    private $tokenExpiry = 24; // hours

    public function __construct() {
        $this->user = new User();
        $this->mailer = new EmailService();
        
        $configPath = __DIR__ . '/../config/mail.php';
        if (!file_exists($configPath)) {
            throw new Exception('Mail configuration file not found');
        }
        $this->config = require $configPath;
    }

    public function sendVerificationEmail($user) {
        try {
            $token = bin2hex(random_bytes(32));
            $expiryDate = date('Y-m-d H:i:s', strtotime("+{$this->tokenExpiry} hours"));

            // Update user with verification token
            $updateResult = $this->user->update($user['id'], [
                'verification_token' => $token,
                'token_expiry' => $expiryDate,
                'is_verified' => false
            ]);

            if (!$updateResult) {
                error_log("[EmailVerification] Failed to update user with verification token");
                return false;
            }

            // Get updated user data
            $updatedUser = $this->user->findById($user['id']);
            if (!$updatedUser || !isset($updatedUser['verification_token'])) {
                error_log("[EmailVerification] Failed to retrieve updated user data or token");
                return false;
            }

            // Send email
            $verificationLink = ($this->config['site_url'] ?? 'http://localhost') . "/verify-email.php?token=" . $token;
            
            $emailData = [
                'to' => $updatedUser['email'],
                'subject' => 'Verify Your Email Address',
                'template' => 'email_verification',
                'data' => [
                    'name' => $updatedUser['name'],
                    'verification_link' => $verificationLink,
                    'expiry_hours' => $this->tokenExpiry
                ]
            ];

            $emailResult = $this->mailer->send($emailData);
            if (!$emailResult) {
                error_log("[EmailVerification] Failed to send verification email");
                return false;
            }

            return true;

        } catch (Exception $e) {
            error_log("[EmailVerification] Error: " . $e->getMessage());
            return false;
        }
    }

    public function verifyEmail($token) {
        $user = $this->user->findByField('verification_token', $token);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid verification token'];
        }

        if (strtotime($user['token_expiry']) < time()) {
            return ['success' => false, 'message' => 'Verification token has expired'];
        }

        $updateResult = $this->user->update($user['id'], [
            'is_verified' => true,
            'verification_token' => null,
            'token_expiry' => null,
            'email_verified_at' => date('Y-m-d H:i:s')
        ]);

        if ($updateResult) {
            return ['success' => true, 'message' => 'Email verified successfully'];
        }

        return ['success' => false, 'message' => 'Failed to verify email'];
    }
} 
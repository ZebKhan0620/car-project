<?php

class EmailService {
    private $from;
    private $templatePath;
    private $config;

    public function __construct() {
        $configPath = __DIR__ . '/../config/mail.php';
        if (!file_exists($configPath)) {
            throw new Exception('Mail configuration file not found');
        }
        
        $this->config = require $configPath;
        $this->from = $this->config['from_address'] ?? 'noreply@example.com';
        $this->templatePath = __DIR__ . '/../templates/emails/';
        
        if (!is_dir($this->templatePath)) {
            mkdir($this->templatePath, 0755, true);
        }
    }

    public function send($emailData) {
        try {
            // Validate required data
            if (!isset($emailData['to']) || !isset($emailData['subject']) || !isset($emailData['template'])) {
                error_log("[Email] Missing required email data");
                return false;
            }

            // Get template content
            $template = $this->getTemplate($emailData['template']);
            if (!$template) {
                error_log("[Email] Template not found: " . $emailData['template']);
                return false;
            }

            // Replace placeholders in template
            $content = $this->replacePlaceholders($template, $emailData['data'] ?? []);

            // Set headers
            $headers = [
                'From: ' . $this->from,
                'Reply-To: ' . $this->from,
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8'
            ];

            // Send email
            $result = mail(
                $emailData['to'],
                $emailData['subject'],
                $content,
                implode("\r\n", $headers)
            );

            if ($result) {
                error_log("[Email] Successfully sent email to: " . $emailData['to']);
                return true;
            }

            error_log("[Email] Failed to send email to: " . $emailData['to']);
            return false;

        } catch (Exception $e) {
            error_log("[Email] Error sending email: " . $e->getMessage());
            return false;
        }
    }

    private function getTemplate($templateName) {
        $templateFile = $this->templatePath . $templateName . '.html';
        if (!file_exists($templateFile)) {
            return false;
        }
        return file_get_contents($templateFile);
    }

    private function replacePlaceholders($template, $data) {
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    public function sendPasswordResetEmail($user) {
        try {
            if (empty($user['id'])) {
                error_log("[EmailService] Invalid user data provided");
                return false;
            }

            $resetToken = bin2hex(random_bytes(32));
            $userModel = new User();
            
            error_log("[EmailService] Attempting to set reset token for user: " . $user['id']);
            
            // Save the reset token to the user record with expiration
            $updateData = [
                'reset_token' => $resetToken,
                'reset_token_expires' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ];
            
            error_log("[EmailService] Update data: " . json_encode($updateData));
            
            $updateResult = $userModel->update($user['id'], $updateData);

            if (!$updateResult['success']) {
                error_log("[EmailService] Failed to save reset token: " . json_encode($updateResult));
                return false;
            }

            $resetLink = "http://localhost/reset-password.php?token=$resetToken";
            error_log("[EmailService] Reset link generated: " . $resetLink);

            // For testing purposes, we'll just return true
            // In production, you would send an actual email
            error_log("[EmailService] Reset token generated successfully for user: " . $user['id']);
            return true;

        } catch (Exception $e) {
            error_log("[EmailService] Error sending password reset email: " . $e->getMessage());
            return false;
        }
    }
} 
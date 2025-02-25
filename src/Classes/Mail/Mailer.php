<?php
namespace Classes\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mailer;
    private $config;
    private $templatePath;

    public function __construct() {
        $configPath = __DIR__ . '/../../config/mail.php';
        if (!file_exists($configPath)) {
            error_log("[Mailer] Config file not found at: " . $configPath);
            throw new Exception('Mail configuration file not found');
        }
        $this->config = require $configPath;
        $this->templatePath = __DIR__ . '/../../templates/emails/';
        if (!is_dir($this->templatePath)) {
            mkdir($this->templatePath, 0755, true);
        }
        $this->mailer = new PHPMailer(true);
        $this->setupMailer();
    }

    private function setupMailer() {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->config['smtp_port'];
            $this->mailer->setFrom($this->config['from_address'], $this->config['from_name']);
            $this->mailer->isHTML(true);
            
            // For development only
            $this->mailer->SMTPDebug = 0;
        } catch (Exception $e) {
            error_log("[Mailer] Setup error: " . $e->getMessage());
        }
    }

    public function sendVerificationEmail($email, $name, $verificationLink): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = __('auth.verification.email.subject');
            
            $template = $this->getEmailTemplate('verification', [
                'name' => $name,
                'verification_link' => $verificationLink,
                'expiry_hours' => 24
            ]);
            
            $this->mailer->Body = $template;
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("[Mailer] Verification email error: " . $e->getMessage());
            return false;
        }
    }

    private function getEmailTemplate($template, $data = []): string {
        $templateFile = $this->templatePath . $template . '.html';
        if (!file_exists($templateFile)) {
            throw new Exception("Email template not found: $template");
        }
        
        $content = file_get_contents($templateFile);
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }
} 
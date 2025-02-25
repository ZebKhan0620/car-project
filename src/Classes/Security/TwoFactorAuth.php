<?php

namespace Classes\Security;

use Models\User as UserModel;
use Classes\Storage\JsonStorage;

class TwoFactorAuth {
    private $user;
    private $storage;
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function __construct() {
        $this->user = new UserModel();
        $this->storage = new JsonStorage('2fa_secrets.json');
    }

    public function setupTwoFactor($userId) {
        try {
            // Generate secret key
            $secret = $this->generateSecret();
            
            // Generate backup codes
            $backupCodes = $this->generateBackupCodes();
            
            // Store in 2FA secrets storage
            $secretData = [
                'user_id' => $userId,
                'secret' => $secret,
                'backup_codes' => $backupCodes,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->storage->insert($secretData);
            
            // Update user settings
            $result = $this->user->update($userId, [
                'two_factor_secret' => $secret,
                'backup_codes' => $backupCodes,
                'settings' => [
                    'two_factor_enabled' => false
                ]
            ]);

            if ($result) {
                error_log("[2FA] Setup completed for user $userId with secret: " . substr($secret, 0, 8) . "...");
                return [
                    'success' => true,
                    'secret' => $secret,
                    'backup_codes' => $backupCodes
                ];
            }

            throw new \Exception('Failed to save 2FA settings');

        } catch (\Exception $e) {
            error_log("[2FA] Setup error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function verifyCode($secret, $code) {
        // Remove spaces and convert to uppercase
        $code = str_replace(' ', '', strtoupper($code));
        
        // Get the current timestamp
        $timestamp = floor(time() / 30);
        
        // Check current and adjacent intervals
        for ($i = -1; $i <= 1; $i++) {
            if ($this->generateCode($secret, $timestamp + $i) === $code) {
                return true;
            }
        }
        
        return false;
    }

    public function verifySetup($userId, $code) {
        // Get user data
        $userData = $this->user->findById($userId);
        if (!$userData) {
            return ['success' => false, 'error' => 'User not found'];
        }

        // Get the secret from the user data
        $secret = $userData['two_factor_secret'] ?? null;
        error_log("[2FA] Verifying setup for user $userId with secret: " . ($secret ? substr($secret, 0, 8) . '...' : 'null'));

        if (!$secret) {
            error_log("[2FA] Secret not found in user data: " . json_encode($userData));
            return ['success' => false, 'error' => '2FA secret not found'];
        }

        // Verify the code using the secret
        if ($this->verifyCode($secret, $code)) {
            // Enable 2FA for the user
            $this->user->update($userId, [
                'settings' => [
                    'two_factor_enabled' => true
                ]
            ]);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Invalid verification code'];
    }

    private function generateCode($secret, $timestamp) {
        // Decode base32 secret
        $secret = $this->base32Decode($secret);
        
        // Pack timestamp into binary string
        $time = pack('N*', 0) . pack('N*', $timestamp);
        
        // Generate HMAC-SHA1 hash
        $hash = hash_hmac('SHA1', $time, $secret, true);
        
        // Get offset
        $offset = ord(substr($hash, -1)) & 0xF;
        
        // Generate 4-byte code
        $code = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        
        // Pad with zeros if necessary
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode($string) {
        $string = strtoupper(trim($string));
        $buffer = 0;
        $resultLength = 0;
        $result = '';
        
        for ($i = 0; $i < strlen($string); $i++) {
            $position = strpos(self::BASE32_CHARS, $string[$i]);
            if ($position === false) {
                continue;
            }
            
            $buffer = ($buffer << 5) | $position;
            $resultLength += 5;
            
            if ($resultLength >= 8) {
                $resultLength -= 8;
                $result .= chr(($buffer >> $resultLength) & 0xFF);
                $buffer &= ((1 << $resultLength) - 1);
            }
        }
        
        return $result;
    }

    private function generateSecret($length = 16) {
        $secret = '';
        $bytes = random_bytes(ceil($length * 5 / 8));
        
        // Convert random bytes to Base32 string
        $binary = '';
        for ($i = 0; $i < strlen($bytes); $i++) {
            $binary .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
        }
        
        // Process 5 bits at a time
        for ($i = 0; $i + 5 <= strlen($binary); $i += 5) {
            $chunk = substr($binary, $i, 5);
            $secret .= self::BASE32_CHARS[bindec($chunk)];
        }
        
        return substr($secret, 0, $length);
    }

    private function generateBackupCodes($count = 8, $length = 10) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = bin2hex(random_bytes($length));
        }
        return $codes;
    }
}

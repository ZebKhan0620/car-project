<?php

use Models\User;
use Classes\Storage\JsonStorage;
use Classes\Security\SecurityNotification;
use Classes\Security\ActivityLogger;
require_once __DIR__ . '/../Security/SecurityNotification.php';
class TwoFactorAuth {
    private $user;
    private $storage;
    private $secretLength = 32;
    private $backupCodes = 8;
    private $codeLength = 6;
    private $timeWindow = 30;

    public function __construct(User $user) {
        $this->user = $user;
        $this->storage = new JsonStorage('2fa_secrets.json');
    }

    public function setupTwoFactor($userId) {
        try {
            error_log("[2FA] Starting setup for user ID: " . $userId);
            
            // Generate secret
            $secret = $this->generateSecret();
            $base32Secret = $this->base32Encode(hex2bin($secret));
            error_log("[2FA] Generated secret: " . $secret);
            
            // Generate backup codes
            $backupCodes = $this->generateBackupCodes();
            error_log("[2FA] Generated " . count($backupCodes) . " backup codes");
            
            // Store 2FA data
            $twoFactorData = [
                'user_id' => $userId,
                'secret' => $secret,
                'backup_codes' => $backupCodes,
                'created_at' => date('Y-m-d H:i:s'),
                'last_used_at' => null
            ];
            
            $this->storage->insert($twoFactorData);
            error_log("[2FA] Stored 2FA data for user ID: " . $userId);
            
            // Enable 2FA for user
            $this->user->update($userId, [
                'settings' => [
                    'two_factor_enabled' => true
                ]
            ]);
            error_log("[2FA] Enabled 2FA for user ID: " . $userId);
            
            // Notify about 2FA enablement
            $notifications = new SecurityNotification();
            $notifications->create($userId, SecurityNotification::SECURITY_SETTINGS_CHANGED, [
                'setting' => '2FA',
                'action' => 'enabled'
            ]);

            // Log 2FA setup activity
            $activityLogger = new ActivityLogger();
            $activityLogger->log($userId, ActivityLogger::TWO_FACTOR, [
                'action' => 'enabled',
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);

            return [
                'success' => true,
                'secret' => $base32Secret,
                'backup_codes' => $backupCodes
            ];
            
        } catch (Exception $e) {
            error_log("[2FA] Setup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to setup 2FA: ' . $e->getMessage()
            ];
        }
    }

    public function verifyCode($userId, $code) {
        try {
            error_log("[2FA] Verifying code for user ID: " . $userId);
            
            // Find the most recent 2FA data for the user
            $userTwoFactorData = $this->storage->findByField('user_id', $userId);
            if (!$userTwoFactorData) {
                throw new Exception('2FA not set up for this user');
            }

            // For initial setup verification, only check the current time window
            $timeSlice = floor(time() / $this->timeWindow);
            $time = pack('N*', 0) . pack('N*', $timeSlice);
            $hm = hash_hmac('SHA1', $time, hex2bin($userTwoFactorData['secret']), true);
            $offset = ord(substr($hm, -1)) & 0x0F;
            $hashpart = substr($hm, $offset, 4);
            $value = unpack('N', $hashpart)[1];
            $value = $value & 0x7FFFFFFF;
            
            $expectedCode = str_pad($value % pow(10, $this->codeLength), $this->codeLength, '0', STR_PAD_LEFT);
            
            if (hash_equals($expectedCode, $code)) {
                error_log("[2FA] Code verified successfully for user ID: " . $userId);
                return [
                    'success' => true,
                    'message' => 'Code verified successfully'
                ];
            }

            // Check if it's a backup code
            if ($this->verifyBackupCode($userTwoFactorData, $code)) {
                error_log("[2FA] Backup code verified successfully for user ID: " . $userId);
                return [
                    'success' => true,
                    'message' => 'Backup code verified successfully'
                ];
            }

            error_log("[2FA] Invalid code for user ID: " . $userId);
            return [
                'success' => false,
                'error' => 'Invalid verification code'
            ];

        } catch (Exception $e) {
            error_log("[2FA] Verification error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Verification failed: ' . $e->getMessage()
            ];
        }
    }

    public function generateTOTPCode($secret) {
        $timeSlice = floor(time() / $this->timeWindow);
        
        // Pack time into binary string
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        
        // Hash it with users secret key
        $hm = hash_hmac('SHA1', $time, hex2bin($secret), true);
        
        // Use last nipple of result as index/offset
        $offset = ord(substr($hm, -1)) & 0x0F;
        
        // grab 4 bytes of the result
        $hashpart = substr($hm, $offset, 4);
        
        // Unpack binary value
        $value = unpack('N', $hashpart)[1];
        
        // Only 32 bits
        $value = $value & 0x7FFFFFFF;
        
        // Generate code
        return str_pad($value % pow(10, $this->codeLength), $this->codeLength, '0', STR_PAD_LEFT);
    }

    private function verifyTOTPCode($secret, $code) {
        // Check current and adjacent time windows
        for ($timeOffset = -1; $timeOffset <= 1; $timeOffset++) {
            $currentTime = time() + ($timeOffset * $this->timeWindow);
            $timeSlice = floor($currentTime / $this->timeWindow);
            
            // Pack time into binary string
            $time = pack('N*', 0) . pack('N*', $timeSlice);
            
            // Hash it with users secret key
            $hm = hash_hmac('SHA1', $time, hex2bin($secret), true);
            
            // Use last nipple of result as index/offset
            $offset = ord(substr($hm, -1)) & 0x0F;
            
            // grab 4 bytes of the result
            $hashpart = substr($hm, $offset, 4);
            
            // Unpack binary value
            $value = unpack('N', $hashpart)[1];
            
            // Only 32 bits
            $value = $value & 0x7FFFFFFF;
            
            // Generate code
            $currentCode = str_pad($value % pow(10, $this->codeLength), $this->codeLength, '0', STR_PAD_LEFT);
            
            if (hash_equals($currentCode, $code)) {
                return true;
            }
        }
        return false;
    }

    private function generateSecret() {
        return bin2hex(random_bytes($this->secretLength));
    }

    private function generateBackupCodes() {
        $codes = [];
        for ($i = 0; $i < $this->backupCodes; $i++) {
            $codes[] = bin2hex(random_bytes(4));
        }
        return $codes;
    }

    private function verifyBackupCode($twoFactorData, $code) {
        if (!isset($twoFactorData['backup_codes']) || !is_array($twoFactorData['backup_codes'])) {
            return false;
        }

        $backupCodes = $twoFactorData['backup_codes'];
        $index = array_search($code, $backupCodes);
        
        if ($index !== false) {
            // Remove used backup code
            unset($backupCodes[$index]);
            $backupCodes = array_values($backupCodes);
            
            // Update storage
            $this->storage->update($twoFactorData['id'], [
                'backup_codes' => $backupCodes
            ]);
            
            return true;
        }
        
        return false;
    }

    private function base32Encode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $binary = str_pad($binary, ceil(strlen($binary) / 40) * 40, '0', STR_PAD_RIGHT);
        $encoded = '';
        for ($i = 0; $i < strlen($binary); $i += 5) {
            $encoded .= $alphabet[bindec(substr($binary, $i, 5))];
        }
        return $encoded;
    }
} 
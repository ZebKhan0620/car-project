<?php

use Models\User;
use Classes\Storage\JsonStorage;
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
            // Generate secret
            $secret = $this->generateSecret();
            
            // Generate backup codes
            $backupCodes = $this->generateBackupCodes();
            
            // Store 2FA data
            $twoFactorData = [
                'user_id' => $userId,
                'secret' => $secret,
                'backup_codes' => $backupCodes,
                'created_at' => date('Y-m-d H:i:s'),
                'last_used_at' => null
            ];
            
            $this->storage->insert($twoFactorData);
            
            error_log("[2FA] Setup completed for user: $userId");
            return [
                'success' => true,
                'secret' => $secret,
                'backup_codes' => $backupCodes
            ];
            
        } catch (Exception $e) {
            error_log("[2FA] Setup error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to setup 2FA'];
        }
    }

    public function verifyCode($userId, $code) {
        try {
            $twoFactorData = $this->storage->findByField('user_id', $userId);
            if (!$twoFactorData) {
                return ['success' => false, 'error' => '2FA not setup'];
            }

            // Check if it's a backup code
            if ($this->verifyBackupCode($userId, $code)) {
                return ['success' => true, 'message' => 'Backup code verified'];
            }

            // Verify TOTP code
            $secret = $twoFactorData['secret'];
            $timeWindow = $this->timeWindow;
            
            // Check current and adjacent time windows
            for ($i = -1; $i <= 1; $i++) {
                $calculatedCode = $this->generateTOTPCode($secret, time() + ($i * $timeWindow));
                if (hash_equals($calculatedCode, $code)) {
                    return ['success' => true, 'message' => 'Code verified'];
                }
            }

            return ['success' => false, 'error' => 'Invalid code'];
            
        } catch (Exception $e) {
            error_log("[2FA] Verification error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Verification failed'];
        }
    }

    private function generateSecret() {
        return bin2hex(random_bytes($this->secretLength));
    }

    private function generateBackupCodes() {
        $codes = [];
        for ($i = 0; $i < $this->backupCodes; $i++) {
            $codes[] = bin2hex(random_bytes(4)); // 8 character codes
        }
        return $codes;
    }

    private function generateTOTPCode($secret, $time = null) {
        $time = $time ?? time();
        $timeSlice = floor($time / $this->timeWindow);
        
        $secretKey = $this->base32Decode($secret);
        $timeHex = str_pad(dechex($timeSlice), 16, '0', STR_PAD_LEFT);
        
        $timeBytes = '';
        for ($i = 0; $i < strlen($timeHex); $i += 2) {
            $timeBytes .= chr(hexdec(substr($timeHex, $i, 2)));
        }
        
        $hash = hash_hmac('sha1', $timeBytes, $secretKey, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xF;
        
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, $this->codeLength);
        
        return str_pad($code, $this->codeLength, '0', STR_PAD_LEFT);
    }

    private function verifyBackupCode($userId, $code) {
        $twoFactorData = $this->storage->findByField('user_id', $userId);
        if (!$twoFactorData || empty($twoFactorData['backup_codes'])) {
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

    private function base32Decode($secret) {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $secret = strtoupper($secret);
        $secretLength = strlen($secret);
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';
        
        for ($i = 0; $i < $secretLength; $i++) {
            $buffer <<= 5;
            $buffer += $base32charsFlipped[$secret[$i]];
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer & (0xFF << $bitsLeft)) >> $bitsLeft);
            }
        }
        
        return $result;
    }
} 
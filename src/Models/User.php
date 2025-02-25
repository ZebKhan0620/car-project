<?php

namespace Models;

use Classes\Storage\JsonStorage;
use Exception;

class User {
    private $storage;
    private $validationRules = [
        'name' => ['required', 'min:2', 'max:50'],
        'email' => ['required', 'email', 'unique'],
        'password' => ['required', 'min:8']
    ];

    public function __construct() {
        $this->storage = new JsonStorage('users.json');
    }

    public function create($data) {
        try {
            $data['profile_image'] = $data['profile_image'] ?? 'default-avatar.png';
            // Validate required fields
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                throw new Exception('Missing required fields');
            }

            // Create user data
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'avatar' => null,
                'is_verified' => false,
                'verification_token' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'settings' => [
                    'notifications_enabled' => true,
                    'two_factor_enabled' => false
                ],
                'profile_image' => $data['profile_image']
            ];

            // Check if email already exists
            if ($this->findByEmail($data['email'])) {
                return ['success' => false, 'error' => 'Email already exists'];
            }

            // Insert into storage
            $userId = $this->storage->insert($userData);
            
            if ($userId) {
                $userData['id'] = $userId;
                return ['success' => true, 'user' => $userData];
            }

            return ['success' => false, 'error' => 'Failed to create user'];

        } catch (Exception $e) {
            error_log("[User] Creation error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function findById($id) {
        error_log("[User] Finding user by ID: " . $id);
        return $this->storage->findById($id);
    }

    public function findByEmail($email) {
        error_log("[User] Finding user by email: " . $email);
        $result = $this->storage->findByField('email', $email);
        error_log("[User] Result: " . json_encode($result));
        return $result;
    }

    public function update($id, array $data) {
        error_log("[User] Updating user $id with data: " . json_encode($data));
        
        $user = $this->findById($id);
        if (!$user) {
            error_log("[User] User not found for update: " . $id);
            return ['success' => false, 'message' => 'User not found'];
        }

        // If updating profile image, delete old one first
        if (isset($data['profile_image'])) {
            $oldData = $this->findById($id);
            if ($oldData['profile_image'] && $oldData['profile_image'] !== 'default-avatar.png') {
                $oldImagePath = __DIR__ . '/../../public/uploads/profiles/' . $oldData['profile_image'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        }

        // Only validate if we're updating validated fields
        $fieldsToValidate = array_intersect_key($data, $this->validationRules);
        if (!empty($fieldsToValidate)) {
            $errors = $this->validate($fieldsToValidate, true);
            if (!empty($errors)) {
                error_log("[User] Update validation failed: " . json_encode($errors));
                return ['success' => false, 'errors' => $errors];
            }
        }

        // Update user data
        $updatedUser = array_merge($user, $data);
        
        // Save to storage
        $result = $this->storage->update($id, $updatedUser);
        
        error_log("[User] Update result for ID " . $id . ": " . ($result ? "success" : "failed"));
        return ['success' => $result, 'user' => $result ? $updatedUser : null];
    }

    public function delete($id) {
        error_log("[User] Deleting user: " . $id);
        return $this->storage->delete($id);
    }

    private function validate(array $data, $isUpdate = false) {
        $errors = [];

        foreach ($this->validationRules as $field => $rules) {
            // Skip validation for fields not present in update
            if ($isUpdate && !isset($data[$field])) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'required' && (!isset($data[$field]) || empty($data[$field]))) {
                    $errors[$field][] = ucfirst($field) . ' is required';
                }

                if (strpos($rule, 'min:') === 0) {
                    $min = substr($rule, 4);
                    if (isset($data[$field]) && strlen($data[$field]) < $min) {
                        $errors[$field][] = ucfirst($field) . ' must be at least ' . $min . ' characters';
                    }
                }

                if (strpos($rule, 'max:') === 0) {
                    $max = substr($rule, 4);
                    if (isset($data[$field]) && strlen($data[$field]) > $max) {
                        $errors[$field][] = ucfirst($field) . ' must not exceed ' . $max . ' characters';
                    }
                }

                if ($rule === 'email' && isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Invalid email format';
                }
            }
        }

        return $errors;
    }

    public function verifyEmail($id) {
        return $this->update($id, [
            'is_verified' => true,
            'email_verified_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function updatePassword($id, $newPassword) {
        return $this->update($id, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }

    public function findByField($field, $value) {
        error_log("[User] Finding user by $field: $value");
        return $this->storage->findByField($field, $value);
    }

    public function findByVerificationToken($token) {
        return $this->storage->findByField('verification_token', $token);
    }

    public function findByResetToken($token) {
        error_log("[User] Looking for user with reset token: " . $token);
        
        $users = $this->storage->findAll();
        foreach ($users['items'] as $user) {
            if (isset($user['reset_token']) && $user['reset_token'] === $token) {
                if (isset($user['reset_token_expires'])) {
                    $expires = strtotime($user['reset_token_expires']);
                    if ($expires < time()) {
                        error_log("[User] Reset token expired for user: " . $user['id']);
                        return null;
                    }
                }
                error_log("[User] Found user with valid reset token: " . $user['id']);
                return $user;
            }
        }
        error_log("[User] No user found with token: " . $token);
        return null;
    }

    public function authenticate($email, $password) {
        try {
            error_log("[User] Attempting authentication for email: $email");
            
            // Find user by email
            $user = $this->findByField('email', $email);
            
            if (!$user) {
                error_log("[User] Authentication failed: User not found");
                return ['success' => false, 'error' => 'Invalid email or password'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                error_log("[User] Authentication failed: Invalid password");
                return ['success' => false, 'error' => 'Invalid email or password'];
            }
            
            // Check if email is verified
            if (!$user['is_verified']) {
                error_log("[User] Authentication failed: Email not verified");
                return ['success' => false, 'error' => 'Please verify your email before logging in'];
            }
            
            error_log("[User] Authentication successful for user: " . $user['email']);
            return [
                'success' => true,
                'user' => $user
            ];
            
        } catch (Exception $e) {
            error_log("[User] Authentication error: " . $e->getMessage());
            return ['success' => false, 'error' => 'An error occurred during authentication'];
        }
    }

    public function enable2FA($userId) {
        try {
            $user = $this->findById($userId);
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            // Update user settings
            $settings = $user['settings'] ?? [];
            $settings['two_factor_enabled'] = true;
            
            $result = $this->update($userId, [
                'settings' => $settings
            ]);

            error_log("[User] 2FA enabled for user: " . $userId);
            return ['success' => true, 'message' => '2FA enabled successfully'];

        } catch (Exception $e) {
            error_log("[User] Error enabling 2FA: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to enable 2FA'];
        }
    }

    public function disable2FA($userId) {
        try {
            $user = $this->findById($userId);
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            // Update user settings
            $settings = $user['settings'] ?? [];
            $settings['two_factor_enabled'] = false;
            
            $result = $this->update($userId, [
                'settings' => $settings
            ]);

            error_log("[User] 2FA disabled for user: " . $userId);
            return ['success' => true, 'message' => '2FA disabled successfully'];

        } catch (Exception $e) {
            error_log("[User] Error disabling 2FA: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to disable 2FA'];
        }
    }

    public function has2FAEnabled($userId) {
        $user = $this->findById($userId);
        return $user && 
               isset($user['settings']['two_factor_enabled']) && 
               $user['settings']['two_factor_enabled'] === true;
    }

    /**
     * Log security event
     */
    public function logSecurityEvent($event_type, $data = [], $user_id = null) {
        try {
            $logEntry = [
                'id' => uniqid(),
                'timestamp' => date('Y-m-d H:i:s'),
                'event_type' => $event_type,
                'user_id' => $user_id,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'data' => $data
            ];

            // Use existing JsonStorage
            $storage = new JsonStorage('security_logs.json');
            $currentData = $storage->load();
            
            // Initialize items array if it doesn't exist
            if (!isset($currentData['items'])) {
                $currentData['items'] = [];
            }
            
            // Add new log entry
            $currentData['items'][] = $logEntry;
            
            // Save and update internal data
            $storage->data = $currentData;
            $storage->save();

            error_log("[Security] " . json_encode($logEntry));
            return true;

        } catch (Exception $e) {
            error_log("[SecurityLogger] Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is admin
     */
    public function isAdmin($user_id) {
        $user = $this->findById($user_id);
        return !empty($user['is_admin']);
    }

    // Add 2FA-related methods
}

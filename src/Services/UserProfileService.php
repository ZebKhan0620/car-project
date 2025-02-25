<?php

use Models\User;
class UserProfileService {
    private $user;
    private $fileUploadService;

    public function __construct(User $user, FileUploadService $fileUploadService) {
        $this->user = $user;
        $this->fileUploadService = $fileUploadService;
    }

    public function updateProfile($userId, $data) {
        error_log("[UserProfileService] Updating profile for user ID: $userId");

        // Validate data
        $validator = new ValidationService($data, [
            'name' => 'required|min:2|max:50',
            'email' => 'required|email'
        ]);

        if (!$validator->validate()) {
            error_log("[UserProfileService] Validation failed: " . json_encode($validator->getErrors()));
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        // Update user profile
        $updateResult = $this->user->update($userId, $data);
        if (!$updateResult) {
            error_log("[UserProfileService] Failed to update user profile");
            return ['success' => false, 'errors' => ['profile' => 'Failed to update profile']];
        }

        error_log("[UserProfileService] Profile updated successfully for user ID: $userId");
        return ['success' => true, 'message' => 'Profile updated successfully'];
    }

    public function uploadAvatar($userId, $file) {
        error_log("[UserProfileService] Uploading avatar for user ID: $userId");

        // Upload avatar
        $uploadResult = $this->fileUploadService->upload($file);
        if (!$uploadResult) {
            error_log("[UserProfileService] Avatar upload failed: " . json_encode($this->fileUploadService->getErrors()));
            return ['success' => false, 'errors' => $this->fileUploadService->getErrors()];
        }

        // Update user avatar path
        $updateResult = $this->user->update($userId, ['avatar' => $uploadResult['filename']]);
        if (!$updateResult) {
            error_log("[UserProfileService] Failed to update avatar path");
            return ['success' => false, 'errors' => ['avatar' => 'Failed to update avatar']];
        }

        error_log("[UserProfileService] Avatar uploaded successfully for user ID: $userId");
        return ['success' => true, 'message' => 'Avatar uploaded successfully', 'avatar' => $uploadResult['filename']];
    }

    public function updateSettings($userId, $settings) {
        error_log("[UserProfileService] Updating settings for user ID: $userId");

        // Update user settings
        $updateResult = $this->user->update($userId, ['settings' => $settings]);
        if (!$updateResult) {
            error_log("[UserProfileService] Failed to update user settings");
            return ['success' => false, 'errors' => ['settings' => 'Failed to update settings']];
        }

        error_log("[UserProfileService] Settings updated successfully for user ID: $userId");
        return ['success' => true, 'message' => 'Settings updated successfully'];
    }
} 
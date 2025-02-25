<?php

namespace Classes\Cars;

require_once __DIR__ . '/../Exceptions/ValidationException.php';

use Exception;
use Classes\Exceptions\ValidationException;

class ImageUploader {
    private $uploadDir;
    private $tempDir;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    private $maxFileSize = 5242880; // 5MB
    
    // Image dimensions
    const MAX_WIDTH = 2048;
    const MAX_HEIGHT = 2048;
    const THUMB_WIDTH = 300;
    const THUMB_HEIGHT = 200;

    public function __construct(string $uploadDir = 'car_images') {
        // Ensure absolute paths
        $baseUploadPath = __DIR__ . '/../../../public/uploads';
        $this->uploadDir = $baseUploadPath . '/' . $uploadDir;
        $this->tempDir = $baseUploadPath . '/temp';
        
        // Create all required directories
        $directories = [
            $baseUploadPath,
            $this->uploadDir,
            $this->tempDir,
            $this->uploadDir . '/thumbnails'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    error_log("[ImageUploader] Failed to create directory: " . $dir);
                    throw new \Exception('Failed to create upload directory');
                }
                // Set proper permissions
                chmod($dir, 0755);
            }
        }
    }

    /**
     * Upload and process image
     */
    public function upload(array $file): array {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('car_') . '.' . $extension;
            $filepath = $this->uploadDir . '/' . $filename;

            // Process and save image
            $image = $this->createImage($file['tmp_name']);
            $image = $this->resizeImage($image);
            $this->saveImage($image, $filepath);

            // Create and save thumbnail
            $thumbnail = $this->createThumbnail($image);
            $thumbnailPath = $this->uploadDir . '/thumbnails/' . $filename;
            $this->saveImage($thumbnail, $thumbnailPath);

            // Clean up
            imagedestroy($image);
            imagedestroy($thumbnail);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => '/uploads/car_images/' . $filename
            ];

        } catch (Exception $e) {
            error_log("[ImageUploader] Upload failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(array $file): void {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException('Upload failed: ' . $this->getUploadError($file['error']));
        }

        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new ValidationException('Invalid file type. Allowed types: JPG, PNG, WebP');
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new ValidationException('File too large. Maximum size: 5MB');
        }
    }

    /**
     * Create image resource from uploaded file
     */
    private function createImage(string $filepath) {
        $type = exif_imagetype($filepath);
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filepath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filepath);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filepath);
            default:
                throw new ValidationException('Unsupported image type');
        }
    }

    /**
     * Resize image if needed
     */
    private function resizeImage($image) {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= self::MAX_WIDTH && $height <= self::MAX_HEIGHT) {
            return $image;
        }

        // Calculate new dimensions
        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        // Resize
        imagecopyresampled(
            $resized, $image,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );

        return $resized;
    }

    /**
     * Create thumbnail
     */
    private function createThumbnail($image) {
        $width = imagesx($image);
        $height = imagesy($image);

        // Calculate dimensions
        $ratio = max(
            self::THUMB_WIDTH / $width,
            self::THUMB_HEIGHT / $height
        );

        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        $thumbnail = imagecreatetruecolor(self::THUMB_WIDTH, self::THUMB_HEIGHT);
        
        // Preserve transparency
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        
        // Calculate cropping position
        $srcX = (int)(($newWidth - self::THUMB_WIDTH) / 2 / $ratio);
        $srcY = (int)(($newHeight - self::THUMB_HEIGHT) / 2 / $ratio);

        // Resize and crop
        imagecopyresampled(
            $thumbnail, $image,
            0, 0, $srcX, $srcY,
            self::THUMB_WIDTH, self::THUMB_HEIGHT,
            (int)(self::THUMB_WIDTH / $ratio), (int)(self::THUMB_HEIGHT / $ratio)
        );

        return $thumbnail;
    }

    /**
     * Save image to file
     */
    private function saveImage($image, string $filepath): void {
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        
        switch (strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($image, $filepath, 90);
                break;
            case 'png':
                imagepng($image, $filepath, 9);
                break;
            case 'webp':
                imagewebp($image, $filepath, 90);
                break;
            default:
                throw new ValidationException('Unsupported output format');
        }
    }

    /**
     * Get upload error message
     */
    private function getUploadError(int $code): string {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Delete image and its thumbnail
     */
    public function delete(string $filename): bool {
        $filepath = $this->uploadDir . '/' . $filename;
        $thumbnailPath = $this->uploadDir . '/thumbnails/' . $filename;

        $success = true;

        if (file_exists($filepath)) {
            $success = $success && unlink($filepath);
        }

        if (file_exists($thumbnailPath)) {
            $success = $success && unlink($thumbnailPath);
        }

        return $success;
    }

    /**
     * Upload and process image
     */
    public function uploadTemporary(array $file): array {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('temp_') . '.' . $extension;
            $filepath = $this->tempDir . '/' . $filename;

            // Process and save image
            $image = $this->createImage($file['tmp_name']);
            $image = $this->resizeImage($image);
            $this->saveImage($image, $filepath);

            // Create and save thumbnail
            $thumbnail = $this->createThumbnail($image);
            $thumbnailPath = $this->tempDir . '/thumb_' . $filename;
            $this->saveImage($thumbnail, $thumbnailPath);

            // Clean up
            imagedestroy($image);
            imagedestroy($thumbnail);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => '/uploads/temp/' . $filename
            ];

        } catch (Exception $e) {
            error_log("[ImageUploader] Temporary upload failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Move temporary files to permanent location
     */
    public function makePermanent(string $tempFilename): string {
        $permanentFilename = str_replace('temp_', 'car_', $tempFilename);
        
        // Move main image
        rename(
            $this->tempDir . '/' . $tempFilename,
            $this->uploadDir . '/' . $permanentFilename
        );
        
        // Move thumbnail
        rename(
            $this->tempDir . '/thumb_' . $tempFilename,
            $this->uploadDir . '/thumbnails/' . $permanentFilename
        );

        return $permanentFilename;
    }

    /**
     * Cleanup temporary files
     */
    public function cleanupTemp(): void {
        // Delete files older than 24 hours
        $files = glob($this->tempDir . '/*');
        $now = time();
        
        foreach ($files as $file) {
            if ($now - filemtime($file) > 86400) {
                unlink($file);
            }
        }
    }

    public function getTempDir(): string {
        return $this->tempDir;
    }
}
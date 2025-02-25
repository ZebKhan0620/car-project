<?php
namespace Classes\Cars;

class ImageHandler {
    private $uploadDir;
    private $cacheDir;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    private $maxFileSize = 5242880; // 5MB
    private $defaultImage = '/car-project/public/assets/images/default-car.jpg';

    public function __construct() {
        $this->uploadDir = __DIR__ . '/../../../public/uploads/car_images/';
        $this->cacheDir = __DIR__ . '/../../../public/cache/images/';
        $this->ensureDirectoriesExist();
    }

    private function ensureDirectoriesExist() {
        foreach ([$this->uploadDir, $this->cacheDir] as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public function processImage($file, $carId) {
        try {
            // Validate file
            $this->validateImage($file);

            // Generate unique filename
            $filename = $this->generateFilename($file['name'], $carId);

            // Process and optimize image
            $image = $this->loadImage($file['tmp_name'], $file['type']);
            $this->optimizeImage($image, $file['type'], $this->uploadDir . $filename);

            return $filename;
        } catch (\Exception $e) {
            error_log("Error processing image: " . $e->getMessage());
            throw $e;
        }
    }

    private function validateImage($file) {
        if (!isset($file['type']) || !in_array($file['type'], $this->allowedTypes)) {
            throw new \Exception('Invalid file type. Allowed types: JPEG, PNG, WebP');
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new \Exception('File too large. Maximum size: 5MB');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('Invalid upload attempt');
        }
    }

    private function generateFilename($originalName, $carId) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return 'car_' . $carId . '_' . uniqid() . '.' . $extension;
    }

    private function loadImage($path, $type) {
        switch ($type) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                throw new \Exception('Unsupported image type');
        }
    }

    private function optimizeImage($image, $type, $savePath, $quality = 80) {
        // Calculate new dimensions while maintaining aspect ratio
        $maxWidth = 1200;
        $maxHeight = 800;
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($type === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $resized;
        }

        // Save optimized image
        switch ($type) {
            case 'image/jpeg':
                imagejpeg($image, $savePath, $quality);
                break;
            case 'image/png':
                imagepng($image, $savePath, round(($quality / 100) * 9));
                break;
            case 'image/webp':
                imagewebp($image, $savePath, $quality);
                break;
        }

        imagedestroy($image);
    }

    public function getImageUrl($filename, $size = 'medium') {
        if (empty($filename)) {
            return $this->defaultImage;
        }

        $originalPath = $this->uploadDir . $filename;
        if (!file_exists($originalPath)) {
            return $this->defaultImage;
        }

        // Define size dimensions
        $sizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 400, 'height' => 300],
            'large' => ['width' => 800, 'height' => 600]
        ];

        if (!isset($sizes[$size])) {
            $size = 'medium';
        }

        // Generate cached version if it doesn't exist
        $cachedFilename = pathinfo($filename, PATHINFO_FILENAME) . "_{$size}." . pathinfo($filename, PATHINFO_EXTENSION);
        $cachedPath = $this->cacheDir . $cachedFilename;

        if (!file_exists($cachedPath)) {
            $image = $this->loadImage($originalPath, mime_content_type($originalPath));
            $this->optimizeImage(
                $image,
                mime_content_type($originalPath),
                $cachedPath,
                80
            );
        }

        return '/car-project/public/cache/images/' . $cachedFilename;
    }

    public function deleteImage($filename) {
        if (empty($filename)) return;

        $originalPath = $this->uploadDir . $filename;
        if (file_exists($originalPath)) {
            unlink($originalPath);
        }

        // Clean up cached versions
        $sizes = ['thumbnail', 'medium', 'large'];
        foreach ($sizes as $size) {
            $cachedFilename = pathinfo($filename, PATHINFO_FILENAME) . "_{$size}." . pathinfo($filename, PATHINFO_EXTENSION);
            $cachedPath = $this->cacheDir . $cachedFilename;
            if (file_exists($cachedPath)) {
                unlink($cachedPath);
            }
        }
    }
} 
<?php

class FileUploadService {
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;
    private $errors = [];
    private $isTestMode = false;

    public function __construct($uploadDir = null, $isTestMode = false) {
        // Set default upload directory if none provided
        $this->uploadDir = $uploadDir ?? __DIR__ . '/../../uploads';
        $this->isTestMode = $isTestMode;
        
        // Default allowed MIME types
        $this->allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf'
        ];

        // Default max size (5MB)
        $this->maxSize = 5 * 1024 * 1024;

        $this->initializeUploadDirectory();
    }

    private function initializeUploadDirectory() {
        if (!is_dir($this->uploadDir)) {
            error_log("[FileUploadService] Creating upload directory: " . $this->uploadDir);
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function upload($file, $customFileName = null) {
        if (!$this->validate($file)) {
            return false;
        }

        $fileName = $customFileName ?? $this->generateFileName($file['name']);
        $filePath = $this->uploadDir . '/' . $fileName;

        // Use copy instead of move_uploaded_file for testing
        $uploadFunction = $this->isTestMode ? 'copy' : 'move_uploaded_file';
        
        if ($uploadFunction($file['tmp_name'], $filePath)) {
            error_log("[FileUploadService] File uploaded successfully: " . $fileName);
            return [
                'success' => true,
                'path' => $filePath,
                'filename' => $fileName
            ];
        }

        $this->addError('upload', 'Failed to move uploaded file');
        return false;
    }

    public function validate($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->addError('upload', $this->getUploadErrorMessage($file['error']));
            return false;
        }

        // Check file size
        if ($file['size'] > $this->maxSize) {
            $this->addError('size', 'File size exceeds maximum limit');
            return false;
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            $this->addError('type', 'File type not allowed');
            return false;
        }

        return true;
    }

    public function setAllowedTypes(array $types) {
        $this->allowedTypes = $types;
    }

    public function setMaxSize($size) {
        $this->maxSize = $size;
    }

    public function getErrors() {
        return $this->errors;
    }

    private function generateFileName($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return uniqid() . '_' . time() . '.' . $extension;
    }

    private function addError($key, $message) {
        error_log("[FileUploadService] Error: $message");
        $this->errors[$key] = $message;
    }

    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    public function delete($filename) {
        $filePath = $this->uploadDir . '/' . $filename;
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                error_log("[FileUploadService] File deleted successfully: " . $filename);
                return true;
            }
            error_log("[FileUploadService] Failed to delete file: " . $filename);
        }
        return false;
    }

    public function setTestMode($isTestMode) {
        $this->isTestMode = $isTestMode;
    }
} 
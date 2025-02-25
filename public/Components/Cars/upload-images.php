<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Cars/ImageUploader.php';
require_once __DIR__ . '/../../../src/Classes/Auth/Session.php';
require_once __DIR__ . '/../../../src/Services/SessionService.php';

use Classes\Cars\ImageUploader;
use Classes\Auth\Session;
use Services\SessionService;

$session = Session::getInstance()->start();
$sessionService = new SessionService();

// Require authentication
$sessionService->requireAuth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (empty($_FILES['file'])) {
        throw new Exception('No file uploaded');
    }

    $imageUploader = new ImageUploader();
    $result = $imageUploader->upload($_FILES['file']);

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    echo json_encode([
        'success' => true,
        'filename' => $result['filename']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
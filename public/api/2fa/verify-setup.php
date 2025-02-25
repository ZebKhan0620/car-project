<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Classes\Auth\Session;
use Classes\Auth\CSRF;
use Classes\Security\TwoFactorAuth;
use Services\SessionService;

header('Content-Type: application/json');

try {
    $session = Session::getInstance()->start();
    $csrf = new CSRF();
    
    // Get and validate JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    // Get token from headers
    $headers = getallheaders();
    $token = $headers['X-CSRF-Token'] ?? null;
    
    error_log("[2FA Verify Setup] Received token: " . ($token ? substr($token, 0, 8) . "..." : "none"));
    error_log("[2FA Verify Setup] Input data: " . json_encode($input));
    
    if (!$token || !$csrf->validateToken($token)) {
        throw new Exception('Invalid security token');
    }

    if (!$session->get('user_id')) {
        throw new Exception('Authentication required');
    }

    if (empty($input['code'])) {
        throw new Exception('Verification code is required');
    }

    $twoFactorAuth = new TwoFactorAuth();
    $result = $twoFactorAuth->verifySetup($session->get('user_id'), $input['code']);
    
    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Invalid verification code');
    }

    echo json_encode([
        'success' => true,
        'message' => '2FA setup verified successfully'
    ]);

} catch (Exception $e) {
    error_log("[2FA Verify Setup] Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
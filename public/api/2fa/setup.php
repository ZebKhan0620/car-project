<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

// Define debug mode constant if not already defined
defined('DEBUG_MODE') or define('DEBUG_MODE', false);

// Update imports with full namespaces
use Classes\Auth\Session;
use Classes\Security\TwoFactorAuth;
use Services\SessionService;
use Models\User as UserModel;  // Alias the User model
use Classes\Auth\CSRF;

// Ensure proper error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/error.log');

header('Content-Type: application/json');

try {
    // Initialize services
    $session = Session::getInstance()->start();
    $csrf = new CSRF();
    $sessionService = new SessionService();
    $twoFactorAuth = new TwoFactorAuth();
    
    // Get all headers and log them for debugging
    $headers = getallheaders();
    error_log("[2FA Setup API] Received headers: " . json_encode($headers));
    
    // Check multiple possible header variations
    $token = $headers['X-CSRF-TOKEN'] ?? 
            $headers['X-Csrf-Token'] ?? 
            $headers['x-csrf-token'] ?? 
            null;
    
    error_log("[2FA Setup API] Received token: " . ($token ? substr($token, 0, 8) . "..." : "none"));
    error_log("[2FA Setup API] Session token: " . substr($csrf->getToken(), 0, 8) . "...");
    
    if (!$token) {
        throw new Exception('CSRF token not provided in headers');
    }

    if (!$csrf->validateToken($token)) {
        error_log("[2FA Setup API] Token validation failed - Stored: " . $csrf->getToken() . ", Received: " . $token);
        throw new Exception('Invalid security token');
    }

    // Check if user is logged in
    if (!$session->get('user_id')) {
        throw new Exception('Authentication required');
    }

    // Setup 2FA for the user
    $result = $twoFactorAuth->setupTwoFactor($session->get('user_id'));
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'secret' => $result['secret'],
            'backup_codes' => $result['backup_codes'],
            'qr_url' => "otpauth://totp/CarMarket:" . urlencode($session->get('email')) . 
                       "?secret=" . $result['secret'] . "&issuer=CarMarket"
        ]);
    } else {
        throw new Exception($result['error'] ?? 'Failed to setup 2FA');
    }

} catch (Exception $e) {
    error_log("[2FA Setup API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => DEBUG_MODE ? [
            'session_id' => session_id(),
            'csrf_token' => $csrf->getToken()
        ] : null
    ]);
}
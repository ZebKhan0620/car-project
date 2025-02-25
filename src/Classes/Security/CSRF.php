<?php
namespace Classes\Security;

use Classes\Auth\Session;

class CSRF {
    private $session;
    
    public function __construct() {
        $this->session = Session::getInstance()->start();
        // Ensure token exists immediately upon CSRF instantiation
        $this->ensureToken();
        error_log("[CSRF] Initialized with session ID: " . $this->session->getId());
    }

    private function ensureToken() {
        if (!$this->session->get('csrf_token')) {
            $token = bin2hex(random_bytes(32));
            $this->session->set('csrf_token', $token);
            error_log("[CSRF] Generated initial token: " . substr($token, 0, 8) . "...");
        }
    }

    public function getToken() {
        $token = $this->session->get('csrf_token');
        error_log("[CSRF] Returning token: " . substr($token, 0, 8) . "...");
        return $token;
    }

    public function generateToken() {

        return bin2hex(random_bytes(32));

    }

    public function validateToken($token) {
        $storedToken = $this->session->get('csrf_token');
        error_log("[CSRF] Validating tokens:");
        error_log("[CSRF] - Stored:    " . ($storedToken ? substr($storedToken, 0, 8) : 'not set'));
        error_log("[CSRF] - Submitted: " . ($token ? substr($token, 0, 8) : 'not set'));

        if (!$token || !$storedToken) {
            error_log("[CSRF] Validation failed - Missing token(s)");
            return false;
        }

        $valid = hash_equals($storedToken, $token);
        error_log("[CSRF] Validation " . ($valid ? "passed" : "failed"));

        return $valid;
    }
} 
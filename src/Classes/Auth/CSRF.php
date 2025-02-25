<?php
namespace Classes\Auth;

use Classes\Auth\Session;

class CSRF {
    private $session;
    private $token_name = 'csrf_token';
    
    public function __construct() {
        $this->session = Session::getInstance()->start();
        $this->ensureToken();
    }

    private function ensureToken() {
        if (!$this->session->get('csrf_token')) {
            $token = bin2hex(random_bytes(32));
            $this->session->set('csrf_token', $token);
            error_log("[CSRF] Generated initial token: " . substr($token, 0, 8) . "...");
        }
    }
    
    public function generateToken() {
        $token = bin2hex(random_bytes(32));
        $this->session->set($this->token_name, $token);
        return $token;
    }
    
    public function getToken() {
        if (!$this->session->get($this->token_name)) {
            return $this->generateToken();
        }
        return $this->session->get($this->token_name);
    }
    
    public function validateToken($token) {
        $storedToken = $this->session->get($this->token_name);
        
        error_log("[CSRF] Validating token:");
        error_log("[CSRF] - Stored:    " . ($storedToken ? substr($storedToken, 0, 8) . "..." : "none"));
        error_log("[CSRF] - Submitted: " . ($token ? substr($token, 0, 8) . "..." : "none"));
        error_log("[CSRF] - Session ID: " . $this->session->getId());
        
        if (empty($storedToken) || empty($token)) {
            error_log("[CSRF] Validation failed - empty token(s)");
            return false;
        }
        
        $result = hash_equals($storedToken, $token);
        error_log("[CSRF] Validation " . ($result ? "passed" : "failed"));
        
        return $result;
    }
    
    public function getTokenName() {
        return $this->token_name;
    }
}

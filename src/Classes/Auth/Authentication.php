<?php


class Authentication {
    private $user;
    private $session;

    public function __construct(User $user, Session $session) {
        $this->user = $user;
        $this->session = $session;
    }

    public function register(array $data) {
        return $this->user->create($data);
    }

    public function login($email, $password) {
        try {
            $result = $this->user->authenticate($email, $password);
            
            if ($result['success']) {
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['email'] = $result['user']['email'];
                
                // Regenerate session ID for security
                $this->session->regenerate();
                
                // Generate new CSRF token after login
                $csrf = new CSRF();
                $csrf->generateToken();
                
                return $result;
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("[Auth] Login error: " . $e->getMessage());
            return ['success' => false, 'error' => 'An error occurred during login'];
        }
    }

    public function logout() {
        return $this->session->destroy();
    }

    public function isLoggedIn() {
        return $this->session->get('logged_in') === true;
    }

    public function getCurrentUser() {
        return $this->session->get('user');
    }
}

<?php

namespace Classes\Auth;

class Session {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            error_log("[Session] Started new session: " . session_id());
        }
    }

    public function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            error_log("[Session] Started new session: " . session_id());
        }
        return $this;
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
        error_log("[Session] Set $key: " . substr(json_encode($value), 0, 100));
        return $this;
    }

    public function get($key, $default = null) {
        $value = $_SESSION[$key] ?? $default;
        error_log("[Session] Get $key: " . ($value ? substr(json_encode($value), 0, 100) : 'not found'));
        return $value;
    }

    public function remove($key) {
        unset($_SESSION[$key]);
    }

    public function setFlash($key, $value) {
        $_SESSION['_flash'][$key] = $value;
        error_log("[Session] Set flash $key: " . substr(json_encode($value), 0, 100));
    }

    public function getFlash($key, $default = null) {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        error_log("[Session] Get flash $key: " . ($value ? substr(json_encode($value), 0, 100) : 'not found'));
        return $value;
    }

    public function hasFlash($key) {
        return isset($_SESSION['_flash'][$key]);
    }

    public function clearFlash() {
        unset($_SESSION['_flash']);
    }

    public function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            return true;
        }
        return false;
    }

    public function regenerate($deleteOldSession = true) {
        return session_regenerate_id($deleteOldSession);
    }

    public function isActive() {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function getId() {
        return session_id();
    }
}
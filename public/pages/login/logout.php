<?php
require_once __DIR__ . './../../../src/bootstrap.php';

// Add proper namespace import
use Models\User;
use Classes\Auth\Session;
use Services\SessionService;
use Classes\Language\TranslationManager;

$session = Session::getInstance()->start();
$sessionService = new SessionService();
$translationManager = TranslationManager::getInstance();

// Store current language before destroying session
$currentLocale = $translationManager->getLocale();
$currentLanguage = isset($_COOKIE['language']) ? $_COOKIE['language'] : $currentLocale;

if ($session->get('user_id')) {
    $userId = $session->get('user_id');
    $sessionId = session_id();
    
    // Terminate session with 'User logout' reason
    // Let SessionService handle the activity logging
    $sessionService->terminateSession($sessionId, 'User logout');
    
    // Clear session data
    $session->destroy();
}

// Redirect to login
header(sprintf(
    'Location: /car-project/public/pages/login/login.php?message=%s&type=success&lang=%s',
    urlencode(__('auth.logout.success_message')),
    $currentLanguage
));
exit; 
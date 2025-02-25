<?php
require_once __DIR__ . './../../../src/bootstrap.php';
require_once __DIR__ . '/../../Components/Header/Header.php';
require_once __DIR__ . '/../../../src/Classes/Language/TranslationManager.php';

use Classes\Auth\Session;
use Services\SessionService;
use Models\User;
use Classes\Security\CSRF;
use Classes\Language\TranslationManager;
use Components\Header\Header;

$session = Session::getInstance()->start();
$sessionService = new SessionService();
$user = new User();
$translationManager = TranslationManager::getInstance();
$header = new Header();

try {
    // Get token from URL
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        throw new Exception(__('auth.verification.errors.no_token'));
    }

    // Initialize User model
    $user = new User();
    
    // Find user by verification token
    $userToVerify = $user->findByVerificationToken($token);
    
    if (!$userToVerify) {
        throw new Exception(__('auth.verification.errors.invalid_token'));
    }
    
    // Update user verification status
    $result = $user->update($userToVerify['id'], [
        'is_verified' => true,
        'email_verified_at' => date('Y-m-d H:i:s'),
        'verification_token' => null
    ]);
    
    if (!$result['success']) {
        throw new Exception(__('auth.verification.errors.verification_failed'));
    }
    
    // Show success page
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo $translationManager->getLocale(); ?>" data-theme="carmarket">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo __('auth.verification.title'); ?> - <?php echo __('common.meta.title'); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-base-200">
        <?php echo $header->render(); ?>
        <div class="container flex justify-center items-center mx-auto px-4 py-16">
            <div class="card w-full max-w-md bg-base-100 shadow-xl">
                <div class="card-body text-center">
                    <div class="text-5xl mb-4">✅</div>
                    <h2 class="card-title text-2xl font-bold justify-center mb-4"><?php echo __('auth.verification.success.heading'); ?></h2>
                    <p class="mb-6"><?php echo __('auth.verification.success.message'); ?></p>
                    <div class="flex flex-col gap-3 mb-6">
                        <a href="https://mail.google.com" target="_blank" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="mr-2" viewBox="0 0 16 16">
                                <path d="M.05 3.555L8 8.414l7.95-4.859A2 2 0 0 0 14 2H2A2 2 0 0 0 .05 3.555zM16 4.697l-5.875 3.59L16 11.743V4.697zm-.168 8.108L9.157 8.879 8 9.586l-1.157-.707-6.675 3.926A2 2 0 0 0 2 14h12a2 2 0 0 0 1.832-1.195zM0 11.743l5.875-3.456L0 4.697v7.046z"/>
                            </svg>
                            Open Gmail
                        </a>
                        <a href="https://outlook.live.com" target="_blank" class="btn btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="mr-2" viewBox="0 0 16 16">
                                <path d="M14 3a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h12zM2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2z"/>
                                <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555zM0 4.697v7.104l5.803-3.558L0 4.697zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757zm3.436-.586L16 11.801V4.697l-5.803 3.546z"/>
                            </svg>
                            Open Outlook
                        </a>
                        <a href="https://yahoo.com/mail" target="_blank" class="btn btn-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="mr-2" viewBox="0 0 16 16">
                                <path d="M8 1.5A2.5 2.5 0 0 0 5.5 4h5A2.5 2.5 0 0 0 8 1.5M3 4a3 3 0 0 1 6 0h3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-12a.5.5 0 0 1-.5-.5v-9A.5.5 0 0 1 0 4h3M2 7.5h12M2 10h12"/>
                            </svg>
                            Open Yahoo Mail
                        </a>
                    </div>
                    <a href="/car-project/public/pages/login/login.php" class="btn btn-primary"><?php echo __('auth.verification.success.login_button'); ?></a>
                </div>
            </div>
        </div>
        <script>
            // Check if there's an error message in the URL
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            const type = urlParams.get('type');
            
            if (message && type === 'error') {
                // Show error message
                const alert = document.createElement('div');
                alert.className = 'alert alert-error max-w-md mx-auto mt-4';
                alert.textContent = decodeURIComponent(message);
                document.querySelector('.container').prepend(alert);
            }
        </script>
    </body>
    </html>
    <?php
    exit;
    
} catch (Exception $e) {
    error_log("[Verification] Error: " . $e->getMessage());
    header('Location: /car-project/public/pages/login/login.php?message=' . urlencode($e->getMessage()) . '&type=error');
    exit;
} 
<?php
require_once __DIR__ . './../../../src/bootstrap.php';
require_once __DIR__ . '/../../Components/Header/Header.php';
require_once __DIR__ . '/../../../src/Classes/Language/TranslationManager.php';

use Components\Header\Header;
use Classes\Language\TranslationManager;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$header = new Header();
$translationManager = TranslationManager::getInstance();
$success = $_SESSION['success'] ?? '';
$verificationLink = $_SESSION['verification_link'] ?? '';

// Don't unset verification_link yet as user might refresh the page
unset($_SESSION['success']); 

// If no verification link in session, try to get from error log
if (!$verificationLink) {
    $logFile = __DIR__ . '/../logs/error.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        if (preg_match('/\[EmailVerification\] Verification link generated: (http:\/\/localhost\/car-project\/public\/verify-email\.php\?token=[a-f0-9]+)/', $logContent, $matches)) {
            $verificationLink = $matches[1];
        }
    }
}
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
                <h2 class="card-title text-2xl font-bold justify-center mb-4"><?php echo __('auth.verification.check.heading'); ?></h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success mb-4">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($verificationLink): ?>
                    <div class="bg-base-200 p-6 rounded-lg mb-6">
                        <p class="font-bold mb-4"><?php echo __('auth.verification.check.instruction'); ?></p>
                        <a href="<?php echo htmlspecialchars($verificationLink); ?>" 
                           class="btn btn-primary btn-block mb-4">
                            <?php echo __('auth.verification.check.verify_button'); ?>
                        </a>
                        <div class="text-xs text-base-content/70 break-all">
                            <?php echo __('auth.verification.check.link_label'); ?>: <?php echo htmlspecialchars($verificationLink); ?>
                        </div>
                        <div class="divider">Check your email</div>
                        <div class="flex flex-col gap-3">
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
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <?php echo __('auth.verification.check.no_link_error'); ?>
                    </div>
                <?php endif; ?>

                <div class="divider">OR</div>

                <p class="text-sm mb-4"><?php echo __('auth.verification.check.already_verified'); ?></p>
                <a href="/car-project/public/pages/login/login.php" class="btn btn-outline"><?php echo __('auth.verification.check.login_button'); ?></a>
            </div>
        </div>
    </div>
</body>
</html> 
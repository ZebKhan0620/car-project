<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../Components/Header/Header.php';

use Models\User;
use Classes\Auth\CSRF;
use Classes\Auth\Session;
use Classes\Language\TranslationManager;
use Components\Header\Header;

// Start session and check if user is logged in
$session = Session::getInstance()->start();
$translationManager = TranslationManager::getInstance();
$header = new Header();

if (!$session->get('user_id') && !isset($_SESSION['2fa_setup_pending'])) {
    header('Location: /car-project/public/pages/login/login.php');
    exit;
}

// Initialize CSRF
$csrf = new CSRF();
$csrf_token = $csrf->getToken();

// Get user email
$user = new User();
$userId = $session->get('user_id') ?? $_SESSION['2fa_user_id'];
$userData = $user->findById($userId);
$userEmail = $userData['email'] ?? '';

$title = __('auth.2fa.setup.title') . ' - ' . __('common.meta.title');
?>

<!DOCTYPE html>
<html lang="<?php echo $translationManager->getLocale(); ?>" data-theme="carmarket">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body class="min-h-screen bg-base-200">
    <?php echo $header->render(); ?>
    <div class="container flex justify-center items-center mx-auto px-4 py-16">
        <div class="card w-full max-w-md bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title text-2xl font-bold mb-6"><?php echo __('auth.2fa.setup.heading'); ?></h2>
                
                <!-- Initial setup container -->
                <div id="setupContainer">
                    <!-- QR code and verification form will be displayed here -->
                </div>
                
                <!-- Success and backup codes container -->
                <div id="backupCodesContainer" class="hidden space-y-6">
                    <div class="alert alert-success">
                        2FA has been enabled successfully!
                    </div>

                    <div class="text-center">
                        <h3 class="font-bold text-xl mb-4">Backup Codes</h3>
                        <p class="mb-4 text-sm">Save these backup codes in a secure place. Each code can only be used once.</p>
                        
                        <div id="backupCodesList" class="grid grid-cols-2 gap-2 mb-6">
                            <!-- Backup codes will be inserted here -->
                        </div>

                        <button id="downloadCodes" class="btn btn-outline btn-sm mb-6">
                            Download Backup Codes
                        </button>

                        <div class="divider">After saving your backup codes</div>

                        <a href="/car-project/public/index.php" class="btn btn-primary btn-block">
                            Continue to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.userEmail = <?php echo json_encode($userEmail); ?>;
        window.translations = {
            auth: {
                "2fa": {
                    "setup": <?php echo json_encode([
                        "scan_qr" => __('auth.2fa.setup.scan_qr'),
                        "manual_code" => __('auth.2fa.setup.manual_code'),
                        "manual_hint" => __('auth.2fa.setup.manual_hint'),
                        "enter_code" => __('auth.2fa.setup.enter_code'),
                        "verification_code" => __('auth.2fa.setup.verification_code'),
                        "verify_button" => __('auth.2fa.setup.verify_button'),
                        "success" => __('auth.2fa.setup.success'),
                        "backup_codes" => __('auth.2fa.setup.backup_codes'),
                        "backup_instruction" => __('auth.2fa.setup.backup_instruction'),
                        "download_codes" => __('auth.2fa.setup.download_codes'),
                        "after_backup" => __('auth.2fa.setup.after_backup'),
                        "continue" => __('auth.2fa.setup.continue')
                    ]); ?>
                }
            }
        };
    </script>
    <script src="/car-project/public/js/2fa-setup.js"></script>
</body>
</html>
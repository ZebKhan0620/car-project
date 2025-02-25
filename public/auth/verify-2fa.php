<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../Components/Header/Header.php';

use Classes\Auth\Session;
use Classes\Auth\CSRF;
use Classes\Security\TwoFactorAuth;
use Services\SessionService;
use Models\User;
use Classes\Language\TranslationManager;
use Components\Header\Header;

// Initialize services
$session = Session::getInstance()->start();
$csrf = new CSRF();
$userModel = new User();
$sessionService = new SessionService();
$twoFactorAuth = new TwoFactorAuth();
$translationManager = TranslationManager::getInstance();
$header = new Header();

// Ensure user has a pending 2FA verification
if (!isset($_SESSION['2fa_pending']) || !$_SESSION['2fa_pending']) {
    redirectToLogin(__('auth.errors.login_required'));
}

// Get and validate user data
$userId = $_SESSION['2fa_user_id'] ?? null;
$userEmail = $_SESSION['2fa_email'] ?? '';

if (!$userId) {
    error_log("[2FA] No user ID in session");
    redirectToLogin(__('auth.errors.login_required'));
}

// Get user data from database
$userData = $userModel->findById($userId);
if (!$userData) {
    error_log("[2FA] User not found: $userId");
    redirectToLogin(__('auth.errors.login_required'));
}

$csrf_token = $csrf->getToken();
$error = null;

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("[2FA] Processing verification for user ID: $userId");
        
        if (!isset($_POST['csrf_token']) || !$csrf->validateToken($_POST['csrf_token'])) {
            throw new Exception(__('auth.errors.invalid_token'));
        }

        $code = $_POST['code'] ?? '';
        if (empty($code)) {
            throw new Exception(__('auth.2fa.verify.errors.code_required'));
        }

        $result = $twoFactorAuth->verifySetup($userId, $code);
        
        if ($result['success']) {
            // Create new session
            $sessionId = $sessionService->createSession($userId);
            error_log("[2FA] Created new session: $sessionId for user: $userId");
            
            // Set session data
            $session->set('user_id', $userId);
            $session->set('email', $userEmail);
            $session->set('session_id', $sessionId);
            
            // Clear 2FA pending status
            unset($_SESSION['2fa_pending']);
            unset($_SESSION['2fa_user_id']);
            unset($_SESSION['2fa_email']);

            // Change redirect to index.php
            header('Location: /car-project/public/index.php');
            exit;
        } else {
            throw new Exception($result['error'] ?? __('auth.2fa.verify.errors.invalid_code'));
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("[2FA] Error: " . $e->getMessage());
    }
}

$title = __('auth.2fa.verify.title') . ' - ' . __('common.meta.title');
?>
<!DOCTYPE html>
<html lang="<?php echo $translationManager->getLocale(); ?>" data-theme="carmarket">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200">
    <?php echo $header->render(); ?>
    <div class="container flex justify-center items-center mx-auto px-4 py-16">
        <div class="card w-full max-w-md bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title text-2xl font-bold mb-6"><?php echo __('auth.2fa.verify.heading'); ?></h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <p class="mb-4">Enter verification code for:<br>
                    <strong><?php echo htmlspecialchars($userEmail); ?></strong>
                </p>

                <form method="POST" action="" class="space-y-6" id="verify2FAForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-control">
                        <label class="label" for="code">
                            <span class="label-text"><?php echo __('auth.2fa.verify.instruction'); ?></span>
                        </label>
                        <input type="text" 
                               id="code" 
                               name="code" 
                               class="input input-bordered" 
                               pattern="[0-9]{6}" 
                               inputmode="numeric" 
                               maxlength="6" 
                               autocomplete="off"
                               required>
                    </div>

                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary"><?php echo __('auth.2fa.verify.verify_button'); ?></button>
                    </div>

                    <div class="text-center mt-4">
                        <button type="button" id="useBackupCode" class="btn btn-link"><?php echo __('auth.2fa.verify.use_backup'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('code').focus();
    </script>
</body>
</html>
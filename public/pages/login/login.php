<?php
require_once __DIR__ . './../../../src/bootstrap.php';
require_once __DIR__ . '/../../Components/Header/Header.php';
require_once __DIR__ . '/../../../src/Classes/Language/TranslationManager.php';

use Classes\Auth\Session;
use Classes\Auth\CSRF;  // Updated namespace
use Models\User;
use Services\SessionService;
use Classes\Security\RateLimiter;
use Classes\Language\TranslationManager;
use Components\Header\Header;

$session = Session::getInstance()->start();
$csrf = new CSRF();
$rateLimiter = new RateLimiter();
$sessionService = new SessionService();
$user = new User();
$translationManager = TranslationManager::getInstance();
$header = new Header();

// Initialize CSRF token once and store in session
$csrf_token = $csrf->getToken();
error_log("[Login] Using session token: " . substr($csrf_token, 0, 8) . "...");

$errors = [];
$success = false;

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    if ($rateLimiter->tooManyAttempts($ipAddress)) {
        $blockExpiry = $rateLimiter->getBlockExpiryTime($ipAddress);
        $user->logSecurityEvent('rate_limit_block', [
            'ip' => $ipAddress,
            'blocked_until' => $blockExpiry
        ]);
        
        // Format the time or provide a default message
        $timeMessage = $blockExpiry ? 
            date('H:i:s', strtotime($blockExpiry)) : 
            sprintf('%d minutes', $rateLimiter->decayMinutes);
        
        $errors[] = sprintf(__('auth.login.errors.too_many_attempts'), $timeMessage);
    } else {
        try {
            // Log login attempt
            $user->logSecurityEvent('login_attempt', [
                'email' => $_POST['email']
            ]);

            // Validate CSRF token
            if (!isset($_POST['csrf_token'])) {
                error_log("[Login] Missing CSRF token in POST data");
                throw new Exception(__('auth.errors.token_missing'));
            }

            error_log("[Login] Form submitted with token: " . substr($_POST['csrf_token'], 0, 8) . "...");
            error_log("[Login] Current session token: " . substr($csrf_token, 0, 8) . "...");

            if (!$csrf->validateToken($_POST['csrf_token'])) {
                error_log("[Login] CSRF validation failed");
                // Get new token for form resubmission
                $csrf_token = $csrf->getToken();
                throw new Exception(__('auth.errors.invalid_token'));
            }

            // Validate input
            if (empty($_POST['email']) || empty($_POST['password'])) {
                throw new Exception(__('auth.login.errors.fields_required'));
            }

            // Attempt login
            $result = $user->authenticate($_POST['email'], $_POST['password']);

            if (!$result['success']) {
                $attempts = $rateLimiter->hit($ipAddress);
                $remaining = $rateLimiter->getRemainingAttempts($ipAddress);
                
                $user->logSecurityEvent('login_failure', [
                    'email' => $_POST['email'],
                    'reason' => $result['error'],
                    'attempts' => $attempts,
                    'remaining_attempts' => $remaining
                ]);

                if ($remaining > 0) {
                    // Check if user exists first
                    $existingUser = $user->findByEmail($_POST['email']);
                    if (!$existingUser) {
                        throw new Exception(__('auth.login.errors.user_not_found'));
                    } else {
                        throw new Exception(
                            sprintf(__('auth.login.errors.invalid_credentials_warning'), $remaining)
                        );
                    }
                } else {
                    $blockExpiry = $rateLimiter->getBlockExpiryTime($ipAddress);
                    $user->logSecurityEvent('rate_limit_block', [
                        'ip' => $ipAddress,
                        'blocked_until' => $blockExpiry
                    ]);
                    throw new Exception(sprintf(
                        __('auth.login.errors.account_locked'),
                        date('H:i:s', strtotime($blockExpiry))
                    ));
                }
            }

            // Clear rate limiter on successful login
            $rateLimiter->clear($ipAddress);

            $userData = $result['user'];

            // Check if email is verified
            if (!$userData['is_verified']) {
                // Store email in session for resend verification
                $session->set('pending_verification_email', $userData['email']);
                
                // Redirect to email verification page
                header('Location: /car-project/public/pages/login/email-verification.php');
                exit;
            }

            // Create session first
            $sessionId = $sessionService->createSession($userData['id']);
            $session->set('user_id', $userData['id']);
            $session->set('email', $userData['email']);
            $session->set('session_id', $sessionId);

            // Check if user hasn't set up 2FA yet
            if (!isset($userData['settings']['two_factor_enabled']) || 
                $userData['settings']['two_factor_enabled'] === false) {
                // Store user info for 2FA setup
                $_SESSION['2fa_setup_pending'] = true;
                $_SESSION['2fa_user_id'] = $userData['id'];
                $_SESSION['2fa_email'] = $userData['email'];
                
                header('Location: /car-project/public/settings/2fa.php');
                exit;
            }

            // Then check 2FA status (this will only run if 2FA is enabled)
            if (!empty($userData['settings']['two_factor_enabled'])) {
                // Store 2FA pending status for verification
                $_SESSION['2fa_pending'] = true;
                $_SESSION['2fa_user_id'] = $userData['id'];
                $_SESSION['2fa_email'] = $userData['email'];
                
                header('Location: /car-project/public/auth/verify-2fa.php');
                exit;
            }

            // This line should never be reached now
            header('Location: /car-project/public/index.php');
            exit;

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            error_log("[Login] Error: " . $e->getMessage());
        }
    }
}


// Get messages from URL (for redirects)
$message = $_GET['message'] ?? '';
$messageType = $_GET['type'] ?? 'info';
?>

<!DOCTYPE html>
<html lang="<?php echo $translationManager->getLocale(); ?>" data-theme="carmarket">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('auth.login.title'); ?> - <?php echo __('common.meta.title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200">
    <?php echo $header->render(); ?>
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-md mx-auto">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-2xl mb-4"><?php echo __('auth.login.heading'); ?></h2>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> mb-4">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error mb-4">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-4">
                        <?php 
                            error_log("[Login] Rendering form with token: " . substr($csrf_token, 0, 8) . "...");
                        ?>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="form-control">
                            <label class="label" for="email">
                                <span class="label-text"><?php echo __('auth.login.email_label'); ?></span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="input input-bordered" 
                                   required>
                        </div>

                        <div class="form-control">
                            <label class="label" for="password">
                                <span class="label-text"><?php echo __('auth.login.password_label'); ?></span>
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="input input-bordered" 
                                   required>
                        </div>

                        <div class="form-control mt-6">
                            <button type="submit" class="btn btn-primary"><?php echo __('auth.login.submit_button'); ?></button>
                        </div>

                        <div class="text-center mt-4 space-y-2">
                            <a href="forgot-password.php" class="link link-hover block"><?php echo __('auth.login.forgot_password_link'); ?></a>
                            <a href="/car-project/public/pages/register/register.php" class="link link-hover block"><?php echo __('auth.login.register_link'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
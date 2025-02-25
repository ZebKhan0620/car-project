<?php
require_once __DIR__ . './../../../src/bootstrap.php';

use Classes\Auth\Session;
use Classes\Auth\CSRF;  // Correct namespace
use Classes\Auth\PasswordReset;
use Services\SessionService;
use Models\User;

// Initialize services
$session = Session::getInstance()->start();
$csrf = new CSRF();  // Now using the correctly namespaced class
$sessionService = new SessionService();

// Get token from URL
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: /car-project/public/login.php?message=Invalid or expired reset link&type=error');
    exit;
}

// Get CSRF token for form
$csrf_token = $csrf->getToken();

// Initialize password reset
$passwordReset = new PasswordReset();

// Verify the reset token
$resetData = $passwordReset->verifyToken($token);
if (!$resetData) {
    header('Location: /car-project/public/login.php?message=Invalid or expired reset link&type=error');
    exit;
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || !$csrf->validateToken($_POST['csrf_token'])) {
            throw new Exception('Invalid security token');
        }

        if (empty($_POST['password']) || empty($_POST['password_confirm'])) {
            throw new Exception('All fields are required');
        }

        if ($_POST['password'] !== $_POST['password_confirm']) {
            throw new Exception('Passwords do not match');
        }

        if (strlen($_POST['password']) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        $result = $passwordReset->resetPassword($token, $_POST['password']);
        if ($result) {
            // Add success message to session
            $session->setFlash('message', 'Password reset successful! You can now login.');
            $session->setFlash('message_type', 'success');
            
            // Redirect with absolute path
            header('Location: /car-project/public/login.php');
            exit;
        } else {
            throw new Exception('Failed to reset password. Please try again.');
        }

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        error_log("[PasswordReset] Error: " . $e->getMessage());
    }
}

$title = 'Reset Password - Car Management System';
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Car Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-2xl mb-4">Reset Password</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error mb-4">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($session->hasFlash('message')): ?>
                        <div class="alert alert-<?php echo $session->getFlash('message_type', 'info'); ?> mb-4">
                            <?php echo htmlspecialchars($session->getFlash('message')); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="form-control">
                            <label class="label" for="password">
                                <span class="label-text">New Password</span>
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="input input-bordered" 
                                   required>
                        </div>

                        <div class="form-control">
                            <label class="label" for="password_confirm">
                                <span class="label-text">Confirm New Password</span>
                            </label>
                            <input type="password" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   class="input input-bordered" 
                                   required>
                        </div>

                        <div class="form-control mt-6"></div>
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
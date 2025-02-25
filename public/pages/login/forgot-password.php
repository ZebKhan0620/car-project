<?php
require_once __DIR__ . './../../../src/bootstrap.php';

use Classes\Auth\Session;
use Classes\Auth\CSRF;  // Add correct namespace
use Services\SessionService;

use Classes\Auth\PasswordReset;

$session = Session::getInstance()->start();
$csrf = new CSRF();
$passwordReset = new PasswordReset();

// Get CSRF token
$csrf_token = $csrf->getToken();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !$csrf->validateToken($_POST['csrf_token'])) {
            throw new Exception('Invalid security token');
        }

        // Validate email
        if (empty($_POST['email'])) {
            throw new Exception('Email is required');
        }

        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            throw new Exception('Invalid email format');
        }

        // Create reset token
        $token = $passwordReset->createToken($email);
        if ($token) {
            // Get reset link
            $resetLink = $passwordReset->getResetLink($token);
            error_log("[ForgotPassword] Reset link generated: " . $resetLink);
            
            // Store link in session for demo purposes
            // In production, this would be emailed
            $session->set('reset_link', $resetLink);
            
            $success = true;
        } else {
            throw new Exception('If an account exists with this email, you will receive password reset instructions.');
        }

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        error_log("[ForgotPassword] Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Car Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-2xl mb-4">Forgot Password</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success mb-4">
                            <p>Password reset instructions have been sent.</p>
                            <?php if ($resetLink = $session->get('reset_link')): ?>
                                <p class="mt-2">Demo link: <a href="<?php echo htmlspecialchars($resetLink); ?>" class="link"><?php echo htmlspecialchars($resetLink); ?></a></p>
                            <?php endif; ?>
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
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="form-control">
                            <label class="label" for="email">
                                <span class="label-text">Email</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="input input-bordered" 
                                   required>
                        </div>

                        <div class="form-control mt-6">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>

                        <div class="text-center mt-4">
                            <a href="login.php" class="link link-hover">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
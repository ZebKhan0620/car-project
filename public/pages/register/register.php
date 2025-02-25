<?php
require_once __DIR__ . './../../../src/bootstrap.php';
require_once __DIR__ . '/../../Components/Header/Header.php';
require_once __DIR__ . '/../../../src/Classes/Language/TranslationManager.php';

use Components\Header\Header;
use Classes\Auth\Session;
use Classes\Auth\CSRF;  // Make sure this matches the actual namespace
use Models\User;
use Services\SessionService;
use Classes\Language\TranslationManager;
use Classes\Mail\Mailer;

$session = Session::getInstance()->start();
$csrf = new CSRF();
$sessionService = new SessionService();
$header = new Header();
$translationManager = TranslationManager::getInstance();
$mailer = new Mailer();

// Generate and log initial token
$csrf_token = $csrf->getToken();
error_log("[Register] Initial CSRF token: " . substr($csrf_token, 0, 8) . "...");

// Initialize other services
$user = new User();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("[Register] POST data: " . print_r($_POST, true));
        error_log("[Register] Current session token: " . $session->get('csrf_token'));
        
        if (!isset($_POST['csrf_token'])) {
            throw new Exception(__('auth.errors.token_missing'));
        }

        if (!$csrf->validateToken($_POST['csrf_token'])) {
            throw new Exception(__('auth.errors.invalid_token'));
        }

        // Validate input
        if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password'])) {
            throw new Exception(__('auth.errors.all_fields_required'));
        }

        if ($_POST['password'] !== $_POST['password_confirm']) {
            throw new Exception(__('auth.errors.passwords_mismatch'));
        }

        // Handle profile image upload
        $profileImage = null;
        if (!empty($_FILES['profile_image']['name'])) {
            $uploadDir = __DIR__ . '/../../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileInfo = pathinfo($_FILES['profile_image']['name']);
            $extension = strtolower($fileInfo['extension']);
            
            // Validate file type
            if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
                throw new Exception(__('auth.errors.invalid_image_type'));
            }

            // Validate file size (2MB max)
            if ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                throw new Exception(__('auth.errors.image_too_large'));
            }

            // Generate unique filename
            $profileImage = uniqid('profile_') . '.' . $extension;
            $targetPath = $uploadDir . $profileImage;

            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                throw new Exception(__('auth.errors.image_upload_failed'));
            }
        }

        // Add profile image to user data
        $userData = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'profile_image' => $profileImage
        ];

        // Create user with profile image
        $result = $user->create($userData);

        if ($result['success']) {
            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Update user with verification token
            $updateResult = $user->update($result['user']['id'], [
                'verification_token' => $verificationToken,
                'is_verified' => false
            ]);

            // Generate and log verification link
            $verificationLink = "http://localhost/car-project/public/pages/login/verify-email.php?token=" . $verificationToken;
            
            // Send verification email
            $emailSent = $mailer->sendVerificationEmail(
                $_POST['email'],
                $_POST['name'],
                $verificationLink
            );
            
            if (!$emailSent) {
                error_log("[Register] Failed to send verification email to: " . $_POST['email']);
            } else {
                error_log("[Register] Verification email sent successfully to: " . $_POST['email']);
            }
            
            // Store verification link in session
            $session->set('verification_link', $verificationLink);
            $session->set('success', __('auth.success.registration_complete'));
            
            // Redirect to email verification page
            header('Location: /car-project/public/pages/login/email-verification.php');
            exit;
        } else {
            throw new Exception(__('auth.errors.registration_failed'));
        }

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        error_log("[Register] Error: " . $e->getMessage());
    }
}

// Get fresh token for form
$current_token = $csrf->getToken();
error_log("[Register] Form token: " . substr($current_token, 0, 8) . "...");
?>

<!DOCTYPE html>
<html lang="<?php echo $translationManager->getLocale(); ?>" data-theme="carmarket">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('auth.register.title'); ?> - <?php echo __('common.meta.title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200">
    <?php echo $header->render(); ?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-2xl mb-4"><?php echo __('auth.register.heading'); ?></h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error mb-4">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-4" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($current_token); ?>">
                        
                        <div class="form-control">
                            <label class="label" for="name">
                                <span class="label-text"><?php echo __('auth.register.name_label'); ?></span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   class="input input-bordered" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                   required>
                        </div>

                        <div class="form-control">
                            <label class="label" for="email">
                                <span class="label-text"><?php echo __('auth.register.email_label'); ?></span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="input input-bordered" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   required>
                        </div>

                        <div class="form-control">
                            <label class="label" for="password">
                                <span class="label-text"><?php echo __('auth.register.password_label'); ?></span>
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="input input-bordered" 
                                   required>
                        </div>

                        <div class="form-control">
                            <label class="label" for="password_confirm">
                                <span class="label-text"><?php echo __('auth.register.confirm_password_label'); ?></span>
                            </label>
                            <input type="password" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   class="input input-bordered" 
                                   required>
                        </div>

                        <div class="form-control">
                            <label class="label" for="profile_image">
                                <span class="label-text"><?php echo __('auth.register.profile_image_label'); ?></span>
                            </label>
                            <input type="file" 
                                   id="profile_image" 
                                   name="profile_image" 
                                   class="file-input file-input-bordered w-full" 
                                   accept="image/*">
                            <label class="label">
                                <span class="label-text-alt"><?php echo __('auth.register.image_requirements'); ?></span>
                            </label>
                        </div>

                        <div class="form-control mt-6">
                            <button type="submit" class="btn btn-primary"><?php echo __('auth.register.submit_button'); ?></button>
                        </div>

                        <div class="text-center mt-4">
                            <a href="login.php" class="link link-hover"><?php echo __('auth.register.login_link'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


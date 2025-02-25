<?php
require_once __DIR__ . './../../../src/bootstrap.php';
require_once __DIR__ . './../../../src/Services/EmailVerificationService.php';
require_once __DIR__ . '/../src/Classes/Auth/Session.php';
require_once __DIR__ . '/../src/Classes/Security/CSRF.php';


use Classes\Auth\Session;
$session = new Session();
$csrf = new Security\CSRF();

$title = 'Resend Verification Email - Car Management System';

ob_start(); ?>

<div class="flex justify-center items-center min-h-screen">
    <div class="card w-full max-w-md bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold text-center mb-6">Resend Verification Email</h2>
            
            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-error shadow-lg mb-4">
                    <div>
                        <ul>
                            <?php foreach ($_SESSION['errors'] as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success shadow-lg mb-4">
                    <div>
                        <span><?= $_SESSION['success'] ?></span>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form action="/api/resend-verification.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">

                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Email Address</span>
                    </label>
                    <input type="email" 
                           name="email" 
                           class="input input-bordered" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">Send Verification Email</button>
                </div>
            </form>

            <div class="divider">OR</div>

            <p class="text-center">
                <a href="/login.php" class="link link-primary">Back to Login</a>
            </p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/templates/layout.php'; 
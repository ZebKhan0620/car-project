<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Add proper namespace import
use Models\User;
use Classes\Auth\Session;

// Initialize session and services
$session = Session::getInstance()->start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /car-project/public/login.php');
    exit;
}

$user = new User();
$isAdmin = $user->isAdmin($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Car Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200">
    <div class="navbar bg-base-100 shadow-xl">
        <div class="flex-1">
            <a class="btn btn-ghost normal-case text-xl">Dashboard</a>
        </div>
        <div class="flex-none gap-2">
            <?php if ($isAdmin): ?>
                <a href="/car-project/public/admin/security-logs.php" class="btn btn-primary">
                    Security Logs
                </a>
            <?php endif; ?>
            <a href="/car-project/public/pages/login/logout.php" class="btn btn-error">
                Logout
            </a>
        </div>
    </div>
</body>
</html>
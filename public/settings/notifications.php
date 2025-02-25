<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use Classes\Auth\Session;
use Services\SessionService;
use Classes\Security\SecurityNotification;
$session = Session::getInstance()->start();
$sessionService = new SessionService();
$notifications = new SecurityNotification();

// Require authentication
$sessionService->requireAuth();
$userId = $session->get('user_id');

// Handle mark as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notifications->markAsRead($_POST['notification_id'], $userId);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get unread notifications
$unreadNotifications = $notifications->getUnread($userId);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Notifications - Car Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-2xl font-bold mb-6">Security Notifications</h2>
            
            <?php if (empty($unreadNotifications)): ?>
                <div class="alert alert-info">
                    No new notifications
                </div>
            <?php else: ?>
                <?php foreach ($unreadNotifications as $notification): ?>
                    <div class="alert alert-<?php echo $notification['priority'] === 'high' ? 'error' : ($notification['priority'] === 'medium' ? 'warning' : 'info'); ?> mb-4">
                        <div class="flex justify-between items-start w-full">
                            <div>
                                <?php echo htmlspecialchars($notifications->getNotificationMessage($notification)); ?>
                            </div>
                            <form method="POST" class="ml-4">
                                <input type="hidden" name="notification_id" value="<?php echo htmlspecialchars($notification['id']); ?>">
                                <button type="submit" name="mark_read" class="btn btn-sm btn-ghost">
                                    Mark as Read
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="mt-6">
                <a href="/car-project/public/settings/security.php" class="btn btn-primary">
                    Security Settings
                </a>
            </div>
        </div>
    </div>
</body>
</html> 
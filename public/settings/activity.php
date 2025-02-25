<?php
require_once __DIR__ . '/../../src/bootstrap.php';


use Classes\Auth\Session;
use Services\SessionService;
use Classes\Security\ActivityLogger;
// Initialize services
$session = Session::getInstance()->start();
$sessionService = new SessionService();
$activityLogger = new ActivityLogger();

// Require authentication
$sessionService->requireAuth();
$userId = $session->get('user_id');

// Get user activities
$activities = $activityLogger->getUserActivities($userId);

// Get filter type from query string
$filterType = $_GET['type'] ?? '';
if ($filterType) {
    $activities = array_filter($activities, function($activity) use ($filterType) {
        return $activity['type'] === $filterType;
    });
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activity - Car Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-base-200">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Account Activity</h2>
                
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-outline">
                        Filter Activities
                    </label>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                        <li><a href="?">All Activities</a></li>
                        <li><a href="?type=login">Logins</a></li>
                        <li><a href="?type=security_setting">Security Changes</a></li>
                        <li><a href="?type=two_factor">2FA Activities</a></li>
                        <li><a href="?type=password_change">Password Changes</a></li>
                    </ul>
                </div>
            </div>

            <?php if (empty($activities)): ?>
                <div class="alert alert-info">
                    No activities found.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>IP Address</th>
                                <th>Browser</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <tr class="hover">
                                    <td>
                                        <div class="font-medium">
                                            <?php echo htmlspecialchars($activityLogger->getActivityDescription($activity)); ?>
                                        </div>
                                        <div class="text-sm opacity-70">
                                            <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                                    <td>
                                        <span class="text-sm">
                                            <?php 
                                            $browser = get_browser_name($activity['user_agent']);
                                            echo htmlspecialchars($browser);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($activity['details'])): ?>
                                            <div class="text-sm">
                                                <?php foreach ($activity['details'] as $key => $value): ?>
                                                    <?php if ($key !== 'ip_address' && $key !== 'browser'): ?>
                                                        <div><?php echo htmlspecialchars(ucfirst($key)) . ': ' . htmlspecialchars($value); ?></div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="mt-6">
                <a href="/car-project/public/settings/security.php" class="btn btn-primary">
                    Back to Security Settings
                </a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Helper function to get browser name
function get_browser_name($user_agent) {
    if (strpos($user_agent, 'Firefox') !== false) {
        return 'Firefox';
    } elseif (strpos($user_agent, 'Chrome') !== false) {
        return 'Chrome';
    } elseif (strpos($user_agent, 'Safari') !== false) {
        return 'Safari';
    } elseif (strpos($user_agent, 'Edge') !== false) {
        return 'Edge';
    } else {
        return 'Unknown Browser';
    }
}
?> 
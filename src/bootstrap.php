<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Classes/Cache/CacheManager.php';
require_once __DIR__ . '/helpers.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// intilize error log
error_log("Starting Car Marketplace");

// Set timezone
date_default_timezone_set('UTC');

// Initialize directories
foreach ([
    __DIR__ . '/../logs',
    __DIR__ . '/../data',
    __DIR__ . '/../storage/cache',
    __DIR__ . '/../resources/translations',
    __DIR__ . '/templates/emails',
    __DIR__ . '/config'
] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Initialize translation files if they don't exist
$translationFiles = [
    __DIR__ . '/../resources/translations/messages.en.json' => [
        'common' => [
            'welcome' => 'Car Marketplace',
            'language' => 'Language'
        ]
    ],
    __DIR__ . '/../resources/translations/messages.ja.json' => [
        'common' => [
            'welcome' => 'カーマーケット',
            'language' => '言語'
        ]
    ]
];

foreach ($translationFiles as $file => $defaultContent) {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode($defaultContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// Initialize users.json if it doesn't exist
$usersFile = __DIR__ . '/../data/users.json';
if (!file_exists($usersFile)) {
    file_put_contents($usersFile, '[]');
}

// Add this to your bootstrap.php
$securityLogsFile = __DIR__ . '/../data/security_logs.json';
if (!file_exists($securityLogsFile)) {
    file_put_contents($securityLogsFile, json_encode(['items' => []], JSON_PRETTY_PRINT));
}

// Add this after security_logs.json initialization
$rateLimitsFile = __DIR__ . '/../data/rate_limits.json';
if (!file_exists($rateLimitsFile)) {
    file_put_contents($rateLimitsFile, json_encode(['items' => []], JSON_PRETTY_PRINT));
}

// Initialize password_resets.json if it doesn't exist
$passwordResetsFile = __DIR__ . '/../data/password_resets.json';
if (!file_exists($passwordResetsFile)) {
    file_put_contents($passwordResetsFile, json_encode(['items' => []], JSON_PRETTY_PRINT));
}

// Define constant for base directory
define('BASE_PATH', __DIR__ . '/..');

// Add these lines to your existing bootstrap.php
require_once __DIR__ . '/Classes/Security/SecurityLogger.php';
require_once __DIR__ . '/Classes/Security/SecurityEvents.php';

// Initialize language middleware
use Classes\Middleware\LanguageMiddleware;
$languageMiddleware = new LanguageMiddleware();
$languageMiddleware->handle();

// Add feature scanning support
define('FEATURES_PATH', __DIR__ . '/../features');
if (!is_dir(FEATURES_PATH)) {
    mkdir(FEATURES_PATH, 0755, true);
}

// Add feature scanning function
function scanFeatures() {
    $features = [];
    $featuresDir = FEATURES_PATH;
    
    if (is_dir($featuresDir)) {
        $dirs = new DirectoryIterator($featuresDir);
        foreach ($dirs as $dir) {
            if ($dir->isDir() && !$dir->isDot()) {
                $features[] = $dir->getFilename();
            }
        }
    }
    
    return $features;
}

// Initialize features registry file
$featuresRegistry = __DIR__ . '/../data/features_registry.json';
if (!file_exists($featuresRegistry)) {
    $defaultRegistry = [
        'active_features' => scanFeatures(),
        'last_scan' => time()
    ];
    file_put_contents($featuresRegistry, json_encode($defaultRegistry, JSON_PRETTY_PRINT));
}

// Add feature-related helper functions
function isFeatureEnabled($featureName) {
    $registry = json_decode(file_get_contents(__DIR__ . '/../data/features_registry.json'), true);
    return in_array($featureName, $registry['active_features']);
}

function refreshFeaturesRegistry() {
    $registry = [
        'active_features' => scanFeatures(),
        'last_scan' => time()
    ];
    file_put_contents(__DIR__ . '/../data/features_registry.json', json_encode($registry, JSON_PRETTY_PRINT));
}

// Add this helper function
function human_time_diff($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return "just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . " minute" . ($mins > 1 ? "s" : "");
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "");
    } else {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "");
    }
}

// Test directories setup
$testDirs = [
    __DIR__ . '/../tests/Unit',
    __DIR__ . '/../tests/Integration',
    __DIR__ . '/../tests/Feature'
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

use Classes\Cache\CacheManager;

// Add this line to bootstrap.php
require_once __DIR__ . '/../public/Components/Header/Header.php';

// Add these lines to your existing bootstrap.php
// Define common paths
define('PROJECT_ROOT', '/car-project');
define('BASE_URL', PROJECT_ROOT . '/public');
define('LOGIN_PATH', BASE_URL . '/pages/login/login.php');
define('REGISTER_PATH', BASE_URL . '/pages/register/register.php');
define('INDEX_PATH', BASE_URL . '/index.php');

// Helper function to redirect to login with message
function redirectToLogin($message = null, $type = 'warning') {
    $currentLanguage = isset($_COOKIE['language']) ? $_COOKIE['language'] : 'en';
    $redirectUrl = LOGIN_PATH;
    
    $params = [];
    if ($message) {
        $params['message'] = $message;
        $params['type'] = $type;
    }
    $params['lang'] = $currentLanguage;
    
    if (!empty($params)) {
        $redirectUrl .= '?' . http_build_query($params);
    }
    
    header("Location: " . $redirectUrl);
    exit;
}
<?php
// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/../../../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Disable error display in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/debug.log');

require_once __DIR__ . '/../../../src/Classes/Storage/JsonStorage.php';

use Classes\Storage\JsonStorage;

try {
    // Log the request method and headers
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Content-Type: " . $_SERVER['CONTENT_TYPE'] ?? 'not set');
    
    // Handle preflight request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    }

    // Log raw input
    $rawInput = file_get_contents('php://input');
    error_log("Raw Input: " . $rawInput);

    // Get POST data
    $data = json_decode($rawInput, true);
    error_log("Decoded Data: " . print_r($data, true));
    
    // Validate JSON decode
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    if (empty($data)) {
        throw new Exception('No data received');
    }
    
    if (!isset($data['email'])) {
        throw new Exception('Email is required');
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Create data directory if it doesn't exist
    $dataDir = __DIR__ . '/../../../data';
    if (!file_exists($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    // Initialize storage with just the filename, not the full path
    $storage = new JsonStorage('newsletter_subscribers.json');
    
    // Check if email already exists
    $subscribers = $storage->findAll();
    if (is_array($subscribers)) {
        foreach ($subscribers as $subscriber) {
            if ($subscriber['email'] === $data['email']) {
                // Return 200 status for already subscribed emails
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'You are already subscribed to our newsletter!',
                    'alreadySubscribed' => true
                ]);
                exit();
            }
        }
    }

    // Add new subscriber
    $newSubscriber = [
        'email' => $data['email'],
        'subscribed_at' => date('Y-m-d H:i:s'),
        'status' => 'active'
    ];

    if (!$storage->create($newSubscriber)) {
        throw new Exception('Failed to save subscription');
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Successfully subscribed to newsletter',
        'alreadySubscribed' => false
    ]);

} catch (Exception $e) {
    error_log("Newsletter Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'raw_input' => $rawInput ?? 'none',
            'json_error' => json_last_error_msg()
        ]
    ]);
} 
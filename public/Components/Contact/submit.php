<?php

// Start output buffering
ob_start();

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Contact/ContactStorage.php';
require_once __DIR__ . '/../../../src/Classes/Security/CSRF.php';

use Classes\Contact\ContactStorage;
use Classes\Security\CSRF;
use Classes\Auth\Session;

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN'] ?? '*');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session if not already started
$session = Session::getInstance();
$session->start();

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $response['message'] = 'Method not allowed';
        echo json_encode($response);
        exit;
    }

    // Validate CSRF token
    $csrf = new CSRF();
    if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $response['message'] = 'Invalid security token';
        echo json_encode($response);
        exit;
    }

    // Check honeypot
    if (!empty($_POST['honeypot'])) {
        // Return success to fool spam bots
        $response['success'] = true;
        echo json_encode($response);
        exit;
    }

    // Check rate limit
    $rateLimitKey = 'contact_form_attempts';
    $maxAttempts = 5;
    $attempts = $_SESSION[$rateLimitKey] ?? [];
    $oneHourAgo = time() - 3600;
    
    // Clean up old attempts
    $attempts = array_filter($attempts, function($timestamp) use ($oneHourAgo) {
        return $timestamp > $oneHourAgo;
    });
    
    if (count($attempts) >= $maxAttempts) {
        http_response_code(429);
        $response['message'] = 'Too many attempts. Please try again later.';
        echo json_encode($response);
        exit;
    }

    // Validate required fields
    $required = ['name', 'email', 'subject', 'message', 'contact_purpose'];
    $data = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $response['errors'][$field] = ucfirst($field) . ' is required';
        } else {
            $data[$field] = trim($_POST[$field]);
        }
    }

    // Validate email format
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = 'Invalid email format';
    }

    // Validate phone format if provided
    if (!empty($_POST['phone']) && !preg_match('/^[0-9\-\(\)\/\+\s]*$/', $_POST['phone'])) {
        $response['errors']['phone'] = 'Invalid phone number format';
    }

    // Validate field lengths
    $maxLengths = [
        'name' => 100,
        'email' => 254,
        'phone' => 20,
        'subject' => 200,
        'message' => 3000
    ];

    foreach ($maxLengths as $field => $maxLength) {
        if (!empty($_POST[$field]) && mb_strlen($_POST[$field]) > $maxLength) {
            $response['errors'][$field] = ucfirst($field) . ' must not exceed ' . $maxLength . ' characters';
        }
    }

    // Validate contact purpose
    $validPurposes = ['general', 'sales', 'support', 'partnership'];
    if (!in_array($data['contact_purpose'] ?? '', $validPurposes)) {
        $response['errors']['contact_purpose'] = 'Invalid contact purpose';
    }

    // If there are validation errors, return them
    if (!empty($response['errors'])) {
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Prepare submission data
    $submissionData = [
        'name' => htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8'),
        'email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
        'phone' => !empty($_POST['phone']) ? htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8') : '',
        'subject' => htmlspecialchars($data['subject'], ENT_QUOTES, 'UTF-8'),
        'message' => htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8'),
        'contact_purpose' => htmlspecialchars($data['contact_purpose'], ENT_QUOTES, 'UTF-8'),
        'ip_address' => hash('sha256', $_SERVER['REMOTE_ADDR']), // Hash IP for privacy
        'user_agent' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8'),
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Save submission
    $contactStorage = new ContactStorage();
    if ($contactStorage->saveSubmission($submissionData)) {
        // Update rate limit
        $attempts[] = time();
        $_SESSION[$rateLimitKey] = $attempts;

        $response['success'] = true;
        $response['message'] = 'Thank you for your message! We\'ll get back to you soon.';
    } else {
        throw new Exception('Failed to save submission');
    }

} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'An error occurred while processing your request. Please try again later.';
}

echo json_encode($response);

// End output buffer
ob_end_flush(); 
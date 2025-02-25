<?php
// Start output buffering
ob_start();

// Set headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Fix the path resolution
$projectRoot = dirname(dirname(dirname(dirname(__FILE__))));
require_once $projectRoot . '/src/bootstrap.php';

use Classes\Meeting\MeetingScheduler;
use Classes\Auth\Session;

try {
    // Clear any previous output
    if (ob_get_length()) ob_clean();

    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log incoming request
    error_log("Cancel meeting request: " . json_encode($input));
    
    if (!$input || json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    if (empty($input['meeting_id'])) {
        throw new Exception('Meeting ID is required');
    }

    // Initialize scheduler and validate session
    $session = Session::getInstance()->start();
    if (!$session->get('user_id')) {
        throw new Exception('Unauthorized', 401);
    }

    $scheduler = new MeetingScheduler();
    $result = $scheduler->cancelMeeting($input['meeting_id']);

    if (!$result) {
        throw new Exception('Failed to cancel meeting');
    }

    // Clear buffer and send success response
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Meeting cancelled successfully'
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Cancel meeting error: " . $e->getMessage());
    
    // Clear buffer and send error response
    ob_clean();
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/error.log');

require_once __DIR__ . '/../../../src/Classes/Meeting/MeetingScheduler.php';

try {
    $scheduler = new Classes\Meeting\MeetingScheduler();
    
    $userId = '123'; // Placeholder user ID
    error_log("Fetching meetings for user: " . $userId);
    
    // Get only active meetings
    $meetings = $scheduler->getUserMeetings($userId, 'scheduled');
    error_log("Found meetings: " . json_encode($meetings));
    
    $response = [
        'success' => true,
        'data' => array_values($meetings)
    ];
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Error in user.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 
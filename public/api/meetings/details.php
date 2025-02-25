<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/error.log');

require_once __DIR__ . '/../../../src/Classes/Meeting/MeetingScheduler.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Meeting ID is required');
    }

    $meetingId = $_GET['id'];
    $scheduler = new Classes\Meeting\MeetingScheduler();
    $meeting = $scheduler->getMeeting($meetingId);

    if (!$meeting) {
        throw new Exception('Meeting not found');
    }

    echo json_encode([
        'success' => true,
        'data' => $meeting
    ]);

} catch (Exception $e) {
    error_log("Error in details.php: " . $e->getMessage());
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 
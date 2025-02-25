<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Meeting/MeetingScheduler.php';
require_once __DIR__ . '/../../../src/Classes/Auth/Session.php';

use Classes\Meeting\MeetingScheduler;
use Classes\Auth\Session;

header('Content-Type: application/json');

try {
    $session = Session::getInstance();
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required = ['car_id', 'meeting_type', 'meeting_date', 'time_slot', 'name', 'email'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Create meeting data
    $meetingData = [
        'car_id' => $_POST['car_id'],
        'type' => $_POST['meeting_type'],
        'date' => $_POST['meeting_date'],
        'slot_id' => $_POST['time_slot'],
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'] ?? '',
        'timezone' => date_default_timezone_get(),
        'duration' => 30, // Default duration in minutes
    ];

    // Schedule the meeting
    $scheduler = new MeetingScheduler();
    $result = $scheduler->schedule($meetingData);

    if ($result) {
        echo json_encode(['success' => true, 'meeting_id' => $result['id']]);
    } else {
        throw new Exception('Failed to schedule meeting');
    }

} catch (Exception $e) {
    error_log("Meeting scheduling error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 
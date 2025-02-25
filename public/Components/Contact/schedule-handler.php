<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Meeting/MeetingScheduler.php';
require_once __DIR__ . '/../../../src/Classes/Cars/CarListing.php';

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required = ['car_id', 'type', 'date', 'slot_id', 'name', 'email'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Initialize scheduler
    $scheduler = new \Classes\Meeting\MeetingScheduler();
    
    // Prepare meeting data
    $meetingData = [
        'car_id' => $_POST['car_id'],
        'type' => $_POST['type'],
        'scheduled_date' => $_POST['date'],
        'slot_id' => $_POST['slot_id'],
        'customer_name' => $_POST['name'],
        'customer_email' => $_POST['email'],
        'notes' => $_POST['notes'] ?? '',
        'status' => 'scheduled',
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Schedule the meeting
    $result = $scheduler->schedule($meetingData);

    if ($result) {
        // Redirect back to the car view page with success message
        header("Location: /car-project/public/Components/Cars/view.php?id={$_POST['car_id']}&scheduled=success");
        exit;
    } else {
        throw new Exception('Failed to schedule meeting');
    }

} catch (Exception $e) {
    error_log("Scheduling error: " . $e->getMessage());
    
    // Redirect back with error, maintaining the car selection
    $carId = $_POST['car_id'] ?? '';
    if ($carId) {
        header("Location: /car-project/public/Components/Cars/view.php?id={$carId}&scheduled=error&message=" . urlencode($e->getMessage()));
    } else {
        header("Location: /car-project/public/Components/Contact/index.php?error=" . urlencode("Failed to schedule meeting: " . $e->getMessage()));
    }
    exit;
} 
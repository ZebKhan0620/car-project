<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Meeting/Calendar.php';
require_once __DIR__ . '/../../../src/Classes/Meeting/MeetingScheduler.php';
use Classes\Meeting\Calendar;
use Classes\Meeting\MeetingScheduler;

header('Content-Type: application/json');

try {
    $date = $_GET['date'] ?? null;
    $carId = $_GET['carId'] ?? null;

    if (!$date || !$carId) {
        throw new Exception('Missing required parameters');
    }

    $calendar = new Calendar();
    $scheduler = new MeetingScheduler();
    
    // Get available slots for the date
    $availableSlots = $calendar->getAvailableSlots($date);
    
    echo json_encode(array_values($availableSlots));

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 
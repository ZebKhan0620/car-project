<?php
// Prevent any output before headers
ob_start();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../../src/bootstrap.php';
use Classes\Meeting\Calendar;
use Classes\Auth\Session;

try {
    // Get and validate date
    $date = $_GET['date'] ?? date('Y-m-d');
    if (!strtotime($date)) {
        throw new Exception('Invalid date format');
    }

    // Get timezone from session or default to user's timezone
    $session = Session::getInstance()->start();
    $timezone = $_GET['timezone'] ?? $session->get('timezone') ?? 'Asia/Tokyo';
    
    error_log("Processing slots request - Date: $date, Timezone: $timezone");
    
    $calendar = new Calendar($timezone);
    $slots = $calendar->getAvailableSlots($date);
    
    error_log("Generated " . count($slots) . " slots");
    error_log("Slots data: " . json_encode($slots));

    echo json_encode([
        'success' => true,
        'data' => $slots,
        'timezone' => $timezone,
        'date' => $date
    ]);

} catch (Exception $e) {
    error_log("Calendar API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
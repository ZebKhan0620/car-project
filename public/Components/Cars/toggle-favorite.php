<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Classes\Auth\Session;

// Set JSON content type
header('Content-Type: application/json');

try {
    $session = Session::getInstance();
    if (!$session->get('user_id')) {
        throw new Exception('User must be logged in');
    }
    
    $userId = $session->get('user_id');
    $carId = $_POST['listing_id'] ?? null;
    
    if (!$carId) {
        throw new Exception('Car ID is required');
    }
    
    // Load favorites
    $favoritesFile = __DIR__ . '/../../../data/favorites.json';
    $favorites = ['items' => []];
    
    if (file_exists($favoritesFile)) {
        $favorites = json_decode(file_get_contents($favoritesFile), true) ?: ['items' => []];
    }
    
    // Check if already favorited
    $existing = false;
    foreach ($favorites['items'] as $key => $item) {
        if ($item['user_id'] == $userId && $item['car_id'] == $carId) {
            $existing = true;
            unset($favorites['items'][$key]);
            break;
        }
    }
    
    if (!$existing) {
        // Add new favorite
        $favorites['items'][] = [
            'user_id' => $userId,
            'car_id' => $carId,
            'created_at' => date('Y-m-d H:i:s'),
            'favorited_at' => date('Y-m-d H:i:s')
        ];
        $action = 'added';
        $message = 'Added to favorites';
    } else {
        // Reindex array after removal
        $favorites['items'] = array_values($favorites['items']);
        $action = 'removed';
        $message = 'Removed from favorites';
    }
    
    // Save to file with pretty print
    file_put_contents($favoritesFile, json_encode($favorites, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
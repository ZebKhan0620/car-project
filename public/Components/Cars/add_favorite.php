<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Classes\Auth\Session;

header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['car_id'])) {
        throw new Exception('Car ID is required');
    }

    // Get user ID from session
    $session = Session::getInstance()->start();
    if (!$session->get('user_id')) {
        throw new Exception('User must be logged in');
    }
    
    $userId = $session->get('user_id');
    $carId = $input['car_id'];
    
    // Load favorites
    $favoritesFile = __DIR__ . '/../../../data/favorites.json';
    $favorites = ['items' => []];
    
    if (file_exists($favoritesFile)) {
        $favorites = json_decode(file_get_contents($favoritesFile), true);
    }
    
    // Check if already favorited
    $existing = array_filter($favorites['items'], function($item) use ($userId, $carId) {
        return $item['user_id'] == $userId && $item['car_id'] == $carId;
    });
    
    if (empty($existing)) {
        // Add new favorite
        $favorites['items'][] = [
            'user_id' => $userId,
            'car_id' => $carId,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $action = 'added';
        $message = 'Added to favorites';
    } else {
        // Remove favorite
        $favorites['items'] = array_filter($favorites['items'], function($item) use ($userId, $carId) {
            return !($item['user_id'] == $userId && $item['car_id'] == $carId);
        });
        $action = 'removed';
        $message = 'Removed from favorites';
    }
    
    // Save to file
    file_put_contents($favoritesFile, json_encode($favorites, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

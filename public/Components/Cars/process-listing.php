<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Auth/Session.php';
require_once __DIR__ . '/../../../src/Services/SessionService.php';
require_once __DIR__ . '/../../../src/Classes/Cars/CarListing.php';

use Classes\Auth\Session;
use Classes\Auth\CSRF;
use Classes\Cars\CarListing;
use Services\SessionService;

$session = Session::getInstance()->start();
$sessionService = new SessionService();

// Require authentication
$sessionService->requireAuth();
$userId = $session->get('user_id');

// Get old input and flash messages
$oldInput = $session->get('old_input', []);
$error = $session->getFlash('error');
$success = $session->getFlash('success');

// Clear old input
$session->remove('old_input');

// After session initialization
$csrf = new CSRF();  // Now using the correctly namespaced class

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Initialize CarListing
    $carListing = new CarListing();

    // Process images
    $images = !empty($_POST['uploaded_images']) 
        ? array_values(array_filter(explode(',', $_POST['uploaded_images'])))
        : [];
        
    error_log("Processed images: " . print_r($images, true));

    // Prepare listing data
    $listing = [
        'title' => $_POST['brand'] . ' ' . $_POST['model'] . ' ' . $_POST['year'],
        'brand' => $_POST['brand'],
        'model' => $_POST['model'],
        'year' => (int)$_POST['year'],
        'mileage' => (int)$_POST['mileage'],
        'price' => (float)$_POST['price'],
        'body_type' => $_POST['body_type'],
        'transmission' => $_POST['transmission'],
        'fuel_type' => $_POST['fuel_type'],
        'color' => $_POST['color'],
        'location' => $_POST['location'],
        'description' => $_POST['description'],
        'features' => $_POST['features'] ?? [],
        'specifications' => $_POST['specifications'] ?? [],
        'condition' => $_POST['condition'],
        'contact_name' => $_POST['contact_name'],
        'contact_email' => $_POST['contact_email'],
        'contact_phone' => $_POST['contact_phone'] ?? '',
        'contact_method' => $_POST['contact_method'],
        'warranty' => isset($_POST['warranty']),
        'negotiable' => isset($_POST['negotiable']),
        'seller_notes' => $_POST['seller_notes'] ?? '',
        'images' => $images,
        'status' => 'active',
        'user_id' => $session->get('user_id')
    ];

    error_log("Final listing data: " . print_r($listing, true));

    // Add listing
    $listingId = $carListing->add($listing);

    // Set success message
    $session->setFlash('success', 'Listing created successfully!');
    
    // Redirect to the new listing
    header('Location: view.php?id=' . $listingId);
    exit;

} catch (Exception $e) {
    // Store form data in session for repopulation
    $session->set('old_input', $_POST);
    
    // Set error message
    $session->setFlash('error', $e->getMessage());
    
    // Redirect back to form
    header('Location: add-listing.php');
    exit;
}
<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Auth/Session.php';
require_once __DIR__ . '/../../../src/Services/SessionService.php';
require_once __DIR__ . '/../../../src/Classes/Cars/CarListing.php';

use Classes\Auth\Session;
use Services\SessionService;
use Classes\Cars\CarListing;

$session = Session::getInstance()->start();
$sessionService = new SessionService();

// Require authentication
$sessionService->requireAuth();
$userId = $session->get('user_id');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $listingId = $_POST['listing_id'] ?? '';
    $newStatus = $_POST['status'] ?? '';

    if (empty($listingId) || empty($newStatus)) {
        throw new Exception('Missing required fields');
    }

    $carListing = new CarListing();
    $listing = $carListing->getById($listingId);

    // Check if user owns the listing
    if (!$listing || $listing['user_id'] !== $userId) {
        throw new Exception('Unauthorized access');
    }

    // Update status
    if ($carListing->update($listingId, ['status' => $newStatus])) {
        $session->setFlash('success', 'Listing status updated successfully');
    } else {
        throw new Exception('Failed to update status');
    }

} catch (Exception $e) {
    $session->setFlash('error', $e->getMessage());
}

// Redirect back to the listing page or index if referer is not available
$redirectUrl = isset($_SERVER['HTTP_REFERER']) 
    ? $_SERVER['HTTP_REFERER'] 
    : "view.php?id=" . $listingId;

header('Location: ' . $redirectUrl);
exit;
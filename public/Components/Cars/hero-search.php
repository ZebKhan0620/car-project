<?php
// Ensure no output has been sent yet


// Start output buffering at the very beginning
ob_start();


require_once __DIR__ . '/../../../src/bootstrap.php';
require_once __DIR__ . '/../../../src/Classes/Cars/CarListing.php';
require_once __DIR__ . '/../../../src/Classes/Cars/SearchFilter.php';
require_once __DIR__ . '/../../../src/Classes/Search/SearchTracker.php';
require_once __DIR__ . '/../Hero/Hero.php';

use Classes\Cars\CarListing;
use Classes\Cars\SearchFilter;
use Classes\Search\SearchTracker;
use Components\Hero\Hero;

try {
    // Clear any existing output
    if (ob_get_length()) ob_clean();
    
    // Set JSON headers
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    // Initialize components
    $searchFilter = new SearchFilter();
    $carListing = new CarListing();
    $searchTracker = new SearchTracker();
    $hero = new Hero();
    
    // Get and sanitize search parameters
    $searchTerm = isset($_GET['search']) ? trim(strip_tags($_GET['search'])) : '';
    $location = isset($_GET['location']) ? trim(strip_tags($_GET['location'])) : '';
    
    // Track the search term if not empty
    if (!empty($searchTerm)) {
        $searchTracker->trackSearch($searchTerm);
    }
    
    try {
        // Get all listings
        $allListings = $carListing->getAll();
        
        // Filter listings based on search term and location
        $filteredListings = array_filter($allListings, function($car) use ($searchTerm, $location) {
            // If no search criteria, return all
            if (empty($searchTerm) && empty($location)) {
                return true;
            }
            
            // Search term matching
            $matchesSearch = empty($searchTerm) || 
                stripos($car['title'] ?? '', $searchTerm) !== false ||
                stripos($car['brand'] ?? '', $searchTerm) !== false ||
                stripos($car['model'] ?? '', $searchTerm) !== false ||
                stripos($car['description'] ?? '', $searchTerm) !== false;
                
            // Location matching
            $matchesLocation = empty($location) || 
                strcasecmp($car['location'] ?? '', $location) === 0;
                
            return $matchesSearch && $matchesLocation;
        });
        
        // Sort by relevance if there's a search term
        if (!empty($searchTerm)) {
            usort($filteredListings, function($a, $b) use ($searchTerm) {
                $scoreA = 0;
                $scoreB = 0;
                
                // Exact matches score higher
                if (stripos($a['title'], $searchTerm) === 0) $scoreA += 3;
                if (stripos($b['title'], $searchTerm) === 0) $scoreB += 3;
                
                // Brand matches
                if (stripos($a['brand'], $searchTerm) !== false) $scoreA += 2;
                if (stripos($b['brand'], $searchTerm) !== false) $scoreB += 2;
                
                return $scoreB - $scoreA;
            });
        }
        
        // Get the best matching car
        $matchingCar = !empty($filteredListings) ? array_values($filteredListings)[0] : null;
        
        // Start a new output buffer for HTML generation
        ob_start();
        $carCardHtml = $hero->renderCarCard($matchingCar);
        $popularSearchesHtml = $hero->renderPopularSearches();
        ob_end_clean();
        
        // Prepare response
        $response = [
            'success' => true,
            'html' => $carCardHtml,
            'popularSearchesHtml' => $popularSearchesHtml,
            'totalMatches' => count($filteredListings)
        ];
        
        // Clear any previous output
        if (ob_get_length()) ob_clean();
        
        // Send JSON response
        echo json_encode($response, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        
    } catch (\Exception $e) {
        throw new \Exception('Error processing search: ' . $e->getMessage());
    }
    
} catch (\Exception $e) {
    // Log the error
    error_log("Hero search error: " . $e->getMessage());
    
    // Clear any output
    if (ob_get_length()) ob_clean();
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while searching',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}

// End output buffering
ob_end_flush(); 
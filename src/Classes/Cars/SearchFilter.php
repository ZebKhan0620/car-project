<?php
namespace Classes\Cars;

use Classes\Cars\CarListing;

class SearchFilter {
    private $carListing;

    public function __construct() {
        $this->carListing = new CarListing();
    }

    public function filter($params) {
        $listings = $this->carListing->getAll();
        $filtered = [];

        foreach ($listings as $listing) {
            if ($this->matchesFilters($listing, $params)) {
                $filtered[] = $listing;
            }
        }

        // Apply sorting
        if (!empty($params['sort'])) {
            $this->sortListings($filtered, $params['sort']);
        }

        return $filtered;
    }

    private function matchesFilters($listing, $params) {
        // Search query
        if (!empty($params['q'])) {
            $query = strtolower($params['q']);
            $searchable = strtolower($listing['title'] . ' ' . $listing['description']);
            if (strpos($searchable, $query) === false) {
                return false;
            }
        }

        // Status filter - only filter if explicitly requested
        if (isset($params['status']) && !empty($params['status'])) {
            if ($listing['status'] !== $params['status']) {
                return false;
            }
        }
        // Note: Removed the default status filter to show all listings

        // Brand filter
        if (!empty($params['brand']) && strtolower($listing['brand']) !== strtolower($params['brand'])) {
            return false;
        }

        // Price range
        if (!empty($params['min_price']) && $listing['price'] < $params['min_price']) {
            return false;
        }
        if (!empty($params['max_price']) && $listing['price'] > $params['max_price']) {
            return false;
        }

        // Year range
        if (!empty($params['year_from']) && $listing['year'] < $params['year_from']) {
            return false;
        }
        if (!empty($params['year_to']) && $listing['year'] > $params['year_to']) {
            return false;
        }

        // Location
        if (!empty($params['location']) && strtolower($listing['location']) !== strtolower($params['location'])) {
            return false;
        }

        // Features
        if (!empty($params['features']) && is_array($params['features'])) {
            foreach ($params['features'] as $feature) {
                if (!in_array($feature, $listing['features'])) {
                    return false;
                }
            }
        }

        return true;
    }

    private function sortListings(&$listings, $sort) {
        switch ($sort) {
            case 'price_low':
                usort($listings, fn($a, $b) => $a['price'] - $b['price']);
                break;
            case 'price_high':
                usort($listings, fn($a, $b) => $b['price'] - $a['price']);
                break;
            case 'year_new':
                usort($listings, fn($a, $b) => $b['year'] - $a['year']);
                break;
            case 'year_old':
                usort($listings, fn($a, $b) => $a['year'] - $b['year']);
                break;
            case 'latest':
            default:
                usort($listings, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
                break;
        }
    }

    public function getAvailableBrands() {
        $listings = $this->carListing->getAll();
        $brands = array_unique(array_column($listings, 'brand'));
        sort($brands);
        return $brands;
    }

    public function getAvailableLocations() {
        $listings = $this->carListing->getAll();
        $locations = array_unique(array_column($listings, 'location'));
        sort($locations);
        return $locations;
    }
}
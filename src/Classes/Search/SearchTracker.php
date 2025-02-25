<?php
namespace Classes\Search;

use Classes\Auth\Session;

class SearchTracker {
    private $session;
    private $maxPopular = 5; // Show top 5 searches

    public function __construct() {
        $this->session = Session::getInstance();
        $this->initSearches();
    }

    private function initSearches() {
        if (!$this->session->get('popular_searches')) {
            $this->session->set('popular_searches', []);
        }
    }

    public function trackSearch($term) {
        $searches = $this->session->get('popular_searches');
        $term = strtolower(trim($term));
        
        if (isset($searches[$term])) {
            $searches[$term]++;
        } else {
            $searches[$term] = 1;
        }
        
        $this->session->set('popular_searches', $searches);
    }

    public function getPopularSearches() {
        $searches = $this->session->get('popular_searches');
        arsort($searches); // Sort by count, highest first
        
        return array_slice($searches, 0, $this->maxPopular, true);
    }
} 
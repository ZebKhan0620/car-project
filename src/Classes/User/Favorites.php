<?php
namespace User;

class Favorites {
    private $dataFile = __DIR__ . '/../../../data/favorites.json';
    private $data;

    public function __construct() {
        $this->loadData();
    }

    private function loadData() {
        if (file_exists($this->dataFile)) {
            $this->data = json_decode(file_get_contents($this->dataFile), true);
        } else {
            $this->data = ['items' => []];
        }
    }

    private function saveData() {
        file_put_contents($this->dataFile, json_encode($this->data, JSON_PRETTY_PRINT));
    }

    public function addFavorite($userId, $listingId) {
        $userKey = "user_" . $userId;
        
        if (!isset($this->data['items'][$userKey])) {
            $this->data['items'][$userKey] = [
                'favorites' => [],
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        // Check if already favorited
        foreach ($this->data['items'][$userKey]['favorites'] as $favorite) {
            if ($favorite['listing_id'] === $listingId) {
                return false;
            }
        }

        // Add new favorite
        $this->data['items'][$userKey]['favorites'][] = [
            'listing_id' => $listingId,
            'added_at' => date('Y-m-d H:i:s')
        ];
        $this->data['items'][$userKey]['updated_at'] = date('Y-m-d H:i:s');

        $this->saveData();
        return true;
    }

    public function removeFavorite($userId, $listingId) {
        $userKey = "user_" . $userId;
        
        if (!isset($this->data['items'][$userKey])) {
            return false;
        }

        $favorites = &$this->data['items'][$userKey]['favorites'];
        foreach ($favorites as $key => $favorite) {
            if ($favorite['listing_id'] === $listingId) {
                array_splice($favorites, $key, 1);
                $this->data['items'][$userKey]['updated_at'] = date('Y-m-d H:i:s');
                $this->saveData();
                return true;
            }
        }

        return false;
    }

    public function getFavorites($userId) {
        $userKey = "user_" . $userId;
        return isset($this->data['items'][$userKey]) 
            ? $this->data['items'][$userKey]['favorites'] 
            : [];
    }

    public function isFavorite($userId, $listingId) {
        $userKey = "user_" . $userId;
        if (!isset($this->data['items'][$userKey])) {
            return false;
        }

        foreach ($this->data['items'][$userKey]['favorites'] as $favorite) {
            if ($favorite['listing_id'] === $listingId) {
                return true;
            }
        }

        return false;
    }
} 
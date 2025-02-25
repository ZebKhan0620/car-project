<?php

namespace User;

use Classes\Storage\JsonStorage;

class Favorites {
    private $storage;
    private $data;

    public function __construct() {
        $this->storage = new JsonStorage('favorites.json');
        $this->data = $this->storage->load();
    }

    private function saveData() {
        $this->storage->save();
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

        // Add to favorites
        $this->data['items'][$userKey]['favorites'][] = [
            'listing_id' => $listingId,
            'added_at' => date('Y-m-d H:i:s')
        ];
        $this->data['items'][$userKey]['updated_at'] = date('Y-m-d H:i:s');

        // Save the updated data
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
        $favorites = $this->storage->load();
        return array_filter($favorites['items'] ?? [], function($item) use ($userId) {
            return $item['user_id'] == $userId;
        });
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

<?php

namespace Classes\Security;

require_once __DIR__ . '/../Storage/JsonStorage.php';

use Classes\Storage\JsonStorage;

class RateLimiter {
    private $maxAttempts = 5;
    public $decayMinutes = 15;
    private $storage;

    public function __construct() {
        $this->storage = new JsonStorage('rate_limits.json');
    }

    public function hit($key) {
        $data = $this->storage->data;
        
        if (!isset($data['items'][$key])) {
            $data['items'][$key] = [
                'attempts' => 0,
                'last_attempt' => null
            ];
        }

        $record = &$data['items'][$key];
        
        // Reset if decay period has passed
        if ($record['last_attempt'] && strtotime($record['last_attempt']) < strtotime("-{$this->decayMinutes} minutes")) {
            $record['attempts'] = 0;
        }

        $record['attempts']++;
        $record['last_attempt'] = date('Y-m-d H:i:s');

        $this->storage->data = $data;
        $this->storage->save();

        return $record['attempts'];
    }

    public function tooManyAttempts($key) {
        $data = $this->storage->data;
        if (!isset($data['items'][$key])) {
            return false;
        }

        $record = $data['items'][$key];
        
        // Check if the block has expired
        if (strtotime($record['last_attempt']) < strtotime("-{$this->decayMinutes} minutes")) {
            $this->clear($key);
            return false;
        }

        return $record['attempts'] >= $this->maxAttempts;
    }

    public function getRemainingAttempts($key) {
        $data = $this->storage->data;
        if (!isset($data['items'][$key])) {
            return $this->maxAttempts;
        }
        return max(0, $this->maxAttempts - $data['items'][$key]['attempts']);
    }

    public function getBlockExpiryTime($key) {
        $data = $this->storage->data;
        if (!isset($data['items'][$key]) || !isset($data['items'][$key]['last_attempt'])) {
            return date('Y-m-d H:i:s', strtotime("+{$this->decayMinutes} minutes"));
        }

        $lastAttempt = strtotime($data['items'][$key]['last_attempt']);
        return date('Y-m-d H:i:s', $lastAttempt + ($this->decayMinutes * 60));
    }

    public function clear($key) {
        $data = $this->storage->load();
        if (isset($data['items'][$key])) {
            unset($data['items'][$key]);
            $this->storage->data = $data;
            $this->storage->save();
        }
    }
} 
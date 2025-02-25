<?php

namespace Classes\Storage;

class JsonStorage {
    private $filePath;
    public $data;
    
    public function __construct($fileName) {
        $dir = __DIR__ . '/../../../data';
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->filePath = $dir . '/' . $fileName;
        if (!file_exists($this->filePath)) {
            $this->data = ['items' => []];
            $this->save();
        }

        $this->load();
    }
    
    public function getFilePath() {
        return $this->filePath;
    }

    public function load(): array {
        if (!file_exists($this->filePath)) {
            $this->data = ['items' => []];
            $this->save();
            return $this->data;
        }

        $content = file_get_contents($this->filePath);
        if (empty($content)) {
            $this->data = ['items' => []];
            $this->save();
            return $this->data;
        }

        $this->data = json_decode($content, true) ?? ['items' => []];
        return $this->data;
    }

    public function save() {
        if (!isset($this->data['items'])) {
            $this->data['items'] = [];
        }
        return file_put_contents($this->filePath, json_encode($this->data, JSON_PRETTY_PRINT));
    }

    public function insert($item) {
        if (!isset($item['id'])) {
            $item['id'] = $this->getNextId();
        }
        $this->data['items'][] = $item;
        $this->save();
        return $item['id'];
    }

    public function update($id, $data) {
        foreach ($this->data['items'] as $key => $item) {
            if ($item['id'] === $id) {
                $this->data['items'][$key] = array_merge($item, $data);
                return $this->save();
            }
        }
        return false;
    }

    public function findById($id) {
        foreach ($this->data['items'] as $item) {
            if (isset($item['id']) && $item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    public function findByField($field, $value) {
        if (!isset($this->data['items']) || !is_array($this->data['items'])) {
            return null;
        }
        
        foreach ($this->data['items'] as $item) {
            if (isset($item[$field]) && $item[$field] === $value) {
                return $item;
            }
        }
        return null;
    }

    private function getNextId() {
        $maxId = 0;
        foreach ($this->data['items'] as $item) {
            if (isset($item['id']) && $item['id'] > $maxId) {
                $maxId = $item['id'];
            }
        }
        return $maxId + 1;
    }

    public function delete($id) {
        foreach ($this->data['items'] as $key => $item) {
            if ($item['id'] === $id) {
                unset($this->data['items'][$key]);
                $this->data['items'] = array_values($this->data['items']);
                return $this->save();
            }
        }
        return false;
    }

    public function truncate() {
        $this->data = ['items' => []];
        return $this->save();
    }

    public function findAll() {
        return $this->data['items'] ?? [];
    }

    public function getAll() {
        return $this->load();
    }

    public function create(array $data): bool {
        try {
            $currentData = $this->load();
            
            // Initialize items array if it doesn't exist
            if (!isset($currentData['items'])) {
                $currentData['items'] = [];
            }
            
            // Add the new item
            $currentData['items'][] = $data;
            
            // Save and update internal data
            $this->data = $currentData;
            return $this->save();
            
        } catch (\Exception $e) {
            error_log("[JsonStorage] Create error: " . $e->getMessage());
            return false;
        }
    }
}




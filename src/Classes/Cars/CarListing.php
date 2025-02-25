<?php
namespace Classes\Cars;  // Corrected namespace

use Exception;
use Classes\Storage\JsonStorage;

class CarListing {
    private $storage;

    private $dataFile = __DIR__ . '/../../../data/car_listings.json';
    private $data;

    public const BRANDS = [
        'toyota' => ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Land Cruiser'],
        'honda' => ['Civic', 'Accord', 'CR-V', 'Pilot', 'HR-V'],
        'nissan' => ['Altima', 'Maxima', 'Rogue', 'Pathfinder', 'GT-R']
    ];
    
    public const BODY_TYPES = ['Sedan', 'SUV', 'Coupe', 'Hatchback', 'Truck', 'Van'];
    public const TRANSMISSIONS = ['Automatic', 'Manual', 'CVT'];
    public const FUEL_TYPES = ['Petrol', 'Diesel', 'Hybrid', 'Electric'];
    public const COLORS = ['white' => 'White', 'black' => 'Black', 'silver' => 'Silver', 'red' => 'Red', 'blue' => 'Blue'];
    public const LOCATIONS = ['japan', 'dubai', 'philippines'];
    public const CONDITIONS = ['New', 'Like New', 'Excellent', 'Good', 'Fair'];
    public const CONTACT_METHODS = ['email', 'phone', 'both'];
    public const FEATURES = [
        'safety' => [
            'ABS', 'Airbags', 'Traction Control', 'Stability Control', 
            'Parking Sensors', 'Backup Camera', 'Lane Departure Warning'
        ],
        'comfort' => [
            'Air Conditioning', 'Leather Seats', 'Heated Seats', 'Sunroof', 
            'Power Windows', 'Power Seats', 'Cruise Control'
        ],
        'technology' => [
            'Bluetooth', 'Navigation System', 'USB Ports', 'Apple CarPlay', 
            'Android Auto', 'Premium Sound System', 'Wireless Charging'
        ]
    ];
    public const SPECIFICATIONS = [
        'engine_size' => [
            'label' => 'Engine Size',
            'type' => 'text',
            'required' => true
        ],
        'horsepower' => [
            'label' => 'Horsepower',
            'type' => 'number',
            'required' => true,
            'min' => 0
        ],
        'doors' => [
            'label' => 'Doors',
            'type' => 'select',
            'required' => true,
            'options' => ['2', '3', '4', '5']
        ],
        'seats' => [
            'label' => 'Seats',
            'type' => 'select',
            'required' => true,
            'options' => ['2', '4', '5', '6', '7', '8']
        ]
    ];

    public function __construct() {
        $this->loadData();
    }

    private function loadData() {
        error_log("Loading car data from: " . $this->dataFile);
        if (file_exists($this->dataFile)) {
            $content = file_get_contents($this->dataFile);
            error_log("Raw content: " . $content);
            $this->data = json_decode($content, true) ?: ['items' => []];
            error_log("Decoded data: " . print_r($this->data, true));
        } else {
            error_log("Data file does not exist: " . $this->dataFile);
            $this->data = ['items' => []];
            $this->saveData();
        }
    }

    private function saveData() {
        file_put_contents($this->dataFile, json_encode($this->data, JSON_PRETTY_PRINT));
    }

    public function getAll() {
        try {
            return $this->data['items'] ?? [];
        } catch (Exception $e) {
            error_log("Error getting car listings: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            error_log("[CarListing] Looking for car with ID: " . $id);
            if (!isset($this->data['items'])) {
                error_log("[CarListing] No items array found in data");
                return null;
            }
            
            foreach ($this->data['items'] as $listing) {
                if ($listing['id'] === $id) {
                    return $listing;
                }
            }
            return null;
        } catch (Exception $e) {
            error_log("[CarListing] Error getting car by ID: " . $e->getMessage());
            return null;
        }
    }

    public function add($listing) {
        $listing['id'] = 'CAR' . uniqid();
        $listing['created_at'] = date('Y-m-d H:i:s');
        $listing['updated_at'] = date('Y-m-d H:i:s');
        
        $this->data['items'][] = $listing;
        $this->saveData();
        
        return $listing['id'];
    }

    public function update($id, $updates) {
        foreach ($this->data['items'] as &$listing) {
            if ($listing['id'] === $id) {
                $listing = array_merge($listing, $updates);
                $listing['updated_at'] = date('Y-m-d H:i:s');
                $this->saveData();
                return true;
            }
        }
        return false;
    }

    public function delete($id) {
        foreach ($this->data['items'] as $key => $listing) {
            if ($listing['id'] === $id) {
                unset($this->data['items'][$key]);
                $this->data['items'] = array_values($this->data['items']);
                $this->saveData();
                return true;
            }
        }
        return false;
    }

    public function setFeatured($id, $featured = true) {
        foreach ($this->data['items'] as &$listing) {
            if ($listing['id'] === $id) {
                $listing['featured'] = $featured;
                $this->saveData();
                return true;
            }
        }
        return false;
    }
}
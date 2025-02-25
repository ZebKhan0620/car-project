<?php
namespace Classes\Calculator\Business;

use Services\External\ShippingAPIService;
use Classes\Cache\CacheManager;
use Exception;

class ShippingCalculator {
    private $shippingAPI;
    private $cache;
    private const CACHE_TTL = 3600; // 1 hour
    
    public function __construct() {
        $this->shippingAPI = new ShippingAPIService();
        $this->cache = new CacheManager();
    }

    public function calculateTotalShippingCost(array $vehicleDetails, string $origin, string $destination): array {
        try {
            // Input validation
            $this->validateInput($vehicleDetails, $origin, $destination);

            // Check cache first
            $cacheKey = $this->generateCacheKey($vehicleDetails, $origin, $destination);
            if ($cachedResult = $this->cache->get($cacheKey)) {
                return $cachedResult;
            }

            $containerSpecs = $this->getContainerSpecsForVehicle($vehicleDetails);
            
            // API call with retry mechanism
            $retries = 3;
            while ($retries > 0) {
                try {
                    $rates = $this->shippingAPI->getContainerRates($origin, $destination, $containerSpecs);
                    break;
                } catch (Exception $e) {
                    $retries--;
                    if ($retries === 0) {
                        throw new Exception("Failed to fetch shipping rates after 3 attempts");
                    }
                    sleep(1); // Wait before retry
                }
            }

            $result = [
                'base_rate' => $rates['base_rate'] ?? 0,
                'fuel_surcharge' => $rates['fuel_surcharge'] ?? 0,
                'documentation' => $this->getDocumentationFees($origin, $destination),
                'insurance' => $this->calculateInsurance($vehicleDetails['value']),
                'total' => 0
            ];

            // Calculate total with validation
            $result['total'] = array_sum($result);

            // Cache the result
            $this->cache->set($cacheKey, $result, self::CACHE_TTL);

            return $result;

        } catch (Exception $e) {
            error_log("Shipping calculation error: " . $e->getMessage());
            throw new Exception("Failed to calculate shipping cost");
        }
    }

    private function validateInput(array $vehicleDetails, string $origin, string $destination): void {
        $required = ['value', 'weight', 'length', 'width', 'height'];
        foreach ($required as $field) {
            if (!isset($vehicleDetails[$field]) || $vehicleDetails[$field] <= 0) {
                throw new Exception("Missing or invalid vehicle $field");
            }
        }

        if (!in_array($origin, ['dubai', 'japan', 'philippines'])) {
            throw new Exception("Invalid origin location");
        }
    }

    private function generateCacheKey(array $vehicleDetails, string $origin, string $destination): string {
        return md5(json_encode([
            'vehicle' => $vehicleDetails,
            'origin' => $origin,
            'destination' => $destination,
            'date' => date('Y-m-d')
        ]));
    }

    private function getContainerSpecsForVehicle(array $vehicle): array {
        return [
            'type' => $vehicle['size'] > 5 ? '40HC' : '20GP',
            'weight' => $vehicle['weight'],
            'length' => $vehicle['length'],
            'width' => $vehicle['width'],
            'height' => $vehicle['height']
        ];
    }

    private function getDocumentationFees(string $origin, string $destination): float {
        return 250.00; // Base documentation fee
    }

    private function calculateInsurance(float $value): float {
        // Standard marine cargo insurance rate
        return $value * 0.015; // 1.5% of vehicle value
    }
}

<?php
namespace Classes\Calculator\Business;

use InvalidArgumentException;
use App\Services\ExchangeRateServiceInterface;

class CostBreakdown {
    private ?ExchangeRateServiceInterface $exchangeService;
    
    public function __construct(?ExchangeRateServiceInterface $exchangeService = null) {
        $this->exchangeService = $exchangeService;
    }

    private function validateInput(array $data): void 
    {
        $requiredFields = ['vehicle_cost', 'shipping', 'duties', 'destination'];
        $numericFields = ['vehicle_cost', 'shipping', 'duties'];
        
        // Check required fields
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === null) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate numeric fields
        foreach ($numericFields as $field) {
            if (!is_numeric($data[$field]) || $data[$field] < 0) {
                throw new InvalidArgumentException("Invalid numeric value for {$field}");
            }
        }

        // Validate destination
        $validDestinations = ['UAE', 'JP', 'PH'];
        if (!in_array($data['destination'], $validDestinations)) {
            throw new InvalidArgumentException("Invalid destination country");
        }
    }
    
    public function calculateBreakdown(array $data): array {
        $breakdown = [
            'base_cost' => $data['price'] ?? 0,
            'taxes' => $this->calculateTaxes($data),
            'fees' => $this->calculateFees($data),
            'shipping' => $data['shipping_cost'] ?? 0
        ];
        
        $breakdown['total'] = array_sum($breakdown);
        return $breakdown;
    }
    
    public function calculateTotalImportCost(array $data): array {
        // Validate input before processing
        $this->validateInput($data);

        $vehicleCost = $data['vehicle_cost'];
        $shippingCost = $data['shipping'];
        $duties = $data['duties'];
        $destination = $data['destination'];

        // Calculate additional fees based on destination
        $processingFee = $this->calculateProcessingFee($destination);
        $insuranceCost = $this->calculateInsurance($vehicleCost);
        
        $total = $vehicleCost + $shippingCost + $duties + $processingFee + $insuranceCost;

        // Calculate percentages making sure they sum to exactly 100%
        $vehiclePercent = round(($vehicleCost / $total) * 100, 2);
        $shippingPercent = round(($shippingCost / $total) * 100, 2);
        $dutiesPercent = round(($duties / $total) * 100, 2);
        $feesPercent = round((($processingFee + $insuranceCost) / $total) * 100, 2);

        // Adjust rounding error to make sum exactly 100%
        $totalPercent = $vehiclePercent + $shippingPercent + $dutiesPercent + $feesPercent;
        $adjustment = 100 - $totalPercent;
        
        // Add any small difference to the largest component
        $percentages = [
            'vehicle' => $vehiclePercent,
            'shipping' => $shippingPercent,
            'duties' => $dutiesPercent,
            'fees' => $feesPercent
        ];
        $largestKey = array_keys($percentages, max($percentages))[0];
        $percentages[$largestKey] += $adjustment;

        return [
            'base_cost' => $vehicleCost,
            'shipping_cost' => $shippingCost,
            'duties' => $duties,
            'processing_fee' => $processingFee,
            'insurance' => $insuranceCost,
            'total' => $total,
            'breakdown_by_percentage' => $percentages
        ];
    }

    private function calculateTaxes(array $data): float {
        $baseAmount = $data['price'] ?? 0;
        $taxRate = $data['tax_rate'] ?? 0.1; // Default 10%
        return $baseAmount * $taxRate;
    }
    
    private function calculateFees(array $data): float {
        $baseAmount = $data['price'] ?? 0;
        $feeRate = $data['fee_rate'] ?? 0.05; // Default 5%
        return $baseAmount * $feeRate;
    }

    private function calculateProcessingFee(string $destination): float {
        $baseFee = 250.00;
        $rates = [
            'UAE' => 1.0,
            'PH' => 0.8
        ];
        return $baseFee * ($rates[$destination] ?? 1.0);
    }

    private function calculateInsurance(float $vehicleValue): float {
        return $vehicleValue * 0.015; // 1.5% of vehicle value
    }
}

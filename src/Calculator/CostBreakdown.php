<?php

namespace App\Calculator;

use App\Services\ExchangeRateServiceInterface;

class CostBreakdown 
{
    private $exchangeRateService;
    
    public function __construct(ExchangeRateServiceInterface $exchangeRateService) 
    {
        $this->exchangeRateService = $exchangeRateService;
    }
    
    public function calculateTotalImportCost($basePrice, $sourceCurrency, $targetCurrency, array $params = []) 
    {
        // Convert base price to target currency
        $convertedPrice = $this->exchangeRateService->convert(
            $basePrice,
            $sourceCurrency,
            $targetCurrency
        );

        // Calculate additional costs
        $shippingCost = $this->calculateShippingCost($params);
        $customsDuty = $this->calculateCustomsDuty($convertedPrice, $params);
        $taxesAndFees = $this->calculateTaxesAndFees($convertedPrice, $params);

        $total = $convertedPrice + $shippingCost + $customsDuty + $taxesAndFees;

        // Calculate risk factors once and reuse
        $riskFactors = $this->calculateRiskFactors($params);
        
        return [
            'total' => $total,
            'breakdown' => [
                'base_price' => $convertedPrice,
                'shipping_cost' => $shippingCost,
                'customs_duty' => $customsDuty,
                'taxes_and_fees' => $taxesAndFees
            ],
            'currency' => $targetCurrency,
            'risk_factors' => $riskFactors, // Use the calculated risk factors
            'recommendation' => $this->generateRecommendation($total, $riskFactors)
        ];
    }

    private function calculateShippingCost(array $params): float 
    {
        // Implement shipping cost calculation based on dimensions, weight, and destination
        $volume = $params['dimensions']['length'] * 
                 $params['dimensions']['width'] * 
                 $params['dimensions']['height'];
        
        $baseRate = 1000; // Base shipping rate
        return $baseRate * ($volume / 10) * ($params['weight'] / 1000);
    }

    private function calculateCustomsDuty(float $basePrice, array $params): float 
    {
        $dutyRates = [
            'UAE' => 0.05,  // 5% for UAE
            'PH' => 0.30,   // 30% for Philippines
            'default' => 0.10 // 10% default
        ];

        $rate = $dutyRates[$params['destination']] ?? $dutyRates['default'];
        
        // Additional duty for older vehicles
        if ($params['age'] > 3) {
            $rate += 0.05;
        }

        return $basePrice * $rate;
    }

    private function calculateTaxesAndFees(float $basePrice, array $params): float 
    {
        $taxRates = [
            'UAE' => 0.05,  // 5% VAT in UAE
            'PH' => 0.12,   // 12% VAT in Philippines
            'default' => 0.10
        ];

        $rate = $taxRates[$params['destination']] ?? $taxRates['default'];
        return $basePrice * $rate;
    }

    private function calculateRiskFactors(array $params): array
    {
        return [
            'age_risk' => min(100, ($params['age'] / 15) * 100),
            'shipping_risk' => min(100, ($params['weight'] / 5000) * 100),
            'market_risk' => $this->getMarketRisk($params['destination'])
        ];
    }

    private function getMarketRisk(string $destination): float
    {
        $marketRisks = [
            'UAE' => 25.0,  // Lower risk market
            'PH' => 45.0,   // Medium risk market
            'default' => 35.0
        ];

        return $marketRisks[$destination] ?? $marketRisks['default'];
    }

    private function generateRecommendation(float $total, array $riskFactors): string
    {
        $avgRisk = array_sum($riskFactors) / count($riskFactors);
        
        // Add total cost consideration
        $costFactor = $total > 50000 ? 'high-value' : ($total > 20000 ? 'medium-value' : 'low-value');
        
        if ($avgRisk < 30) {
            return $costFactor === 'high-value' 
                ? "Low risk investment but high value. Consider insurance." 
                : "Low risk investment. Recommended to proceed.";
        } elseif ($avgRisk < 70) {
            return "Moderate risk. Consider additional insurance and market research.";
        } else {
            return "High risk. Careful consideration required. Additional documentation needed.";
        }
    }
}

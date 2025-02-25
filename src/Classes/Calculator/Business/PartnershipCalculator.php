<?php
namespace Classes\Calculator\Business;

use Services\External\ExchangeRateService;

class PartnershipCalculator {
    private $exchangeService;
    private $setupCosts = [
        'UAE' => [
            'registration' => 25000,
            'license' => 15000,
            'office' => 120000,
            'staff' => 180000,
            'marketing' => 50000
        ],
        'PH' => [
            'registration' => 15000,
            'license' => 8000,
            'office' => 60000,
            'staff' => 90000,
            'marketing' => 30000
        ]
    ];

    private $marketRates = [
        'UAE' => [
            'setup_cost' => 25000,
            'license_fee' => 5000,
            'per_unit_cost' => 2000,
            'profit_margin' => 0.25
        ],
        'PH' => [
            'setup_cost' => 15000,
            'license_fee' => 3000,
            'per_unit_cost' => 1500,
            'profit_margin' => 0.20
        ]
    ];

    public function __construct() {
        $this->exchangeService = new ExchangeRateService();
    }

    public function calculateDetailedInvestment(string $market, int $inventorySize = 10): array {
        $setupCosts = $this->setupCosts[$market];
        $inventoryCost = $this->calculateInventoryCost($market, $inventorySize);
        $operatingCapital = $this->calculateOperatingCapital($market, 6);

        return [
            'setup' => $setupCosts,
            'inventory' => $inventoryCost,
            'operating_capital' => $operatingCapital,
            'total' => array_sum($setupCosts) + $inventoryCost + $operatingCapital
        ];
    }

    public function calculateInitialInvestment(string $market, int $inventorySize): array {
        $rates = $this->marketRates[$market];
        $initialCost = $rates['setup_cost'] + $rates['license_fee'];
        $inventoryCost = $rates['per_unit_cost'] * $inventorySize;

        return [
            'setup_costs' => $rates['setup_cost'],
            'license_fees' => $rates['license_fee'],
            'inventory_investment' => $inventoryCost,
            'total_initial_investment' => $initialCost + $inventoryCost
        ];
    }

    public function calculateMonthlyProjection(string $market, array $params): array {
        $volume = $params['volume'];
        $avgValue = $params['avg_value'];
        $rates = $this->marketRates[$market];
        
        $grossRevenue = $volume * $avgValue;
        $operatingCosts = $this->calculateOperatingCosts($market, $volume);
        $netProfit = $grossRevenue * $rates['profit_margin'];

        return [
            'monthly_revenue' => $grossRevenue,
            'operating_costs' => $operatingCosts,
            'net_profit' => $netProfit,
            'profit_margin' => $rates['profit_margin'] * 100 . '%'
        ];
    }

    public function calculateROI(array $investment, array $monthly): array {
        $annualProfit = $monthly['net_profit'] * 12;
        $totalInvestment = $investment['total_initial_investment'];
        $roi = ($annualProfit / $totalInvestment) * 100;

        return [
            'annual_profit' => $annualProfit,
            'roi_percentage' => round($roi, 2),
            'payback_period' => round($totalInvestment / $monthly['net_profit'], 1)
        ];
    }

    private function calculateInventoryCost(string $market, int $size): float {
        $avgCarCost = ($market === "UAE") ? 35000 : 25000;
        return $avgCarCost * $size;
    }

    private function calculateOperatingCapital(string $market, int $months): float {
        $monthlyOverhead = ($market === "UAE") ? 22000 : 11000;
        return $monthlyOverhead * $months;
    }

    private function calculateOperatingCosts(string $market, int $volume): float {
        $baseOperatingCost = $this->marketRates[$market]['per_unit_cost'] * 0.1;
        return $baseOperatingCost * $volume;
    }
}

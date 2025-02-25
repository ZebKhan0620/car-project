<?php
namespace Classes\Calculator\Business;

class FeasibilityAnalyzer {
    private $marketData = [
        'UAE' => [
            'market_growth' => 0.15,
            'competition_level' => 'medium',
            'demand_score' => 8,
            'risk_factors' => [
                'market_volatility' => 0.2,
                'regulatory_changes' => 0.15,
                'economic_stability' => 0.1
            ]
        ],
        'PH' => [
            'market_growth' => 0.22,
            'competition_level' => 'low',
            'demand_score' => 7,
            'risk_factors' => [
                'market_volatility' => 0.3,
                'regulatory_changes' => 0.25,
                'economic_stability' => 0.2
            ]
        ]
    ];

    public function analyzeFeasibility(string $market, array $financials): array {
        $marketMetrics = $this->marketData[$market];
        $riskScore = $this->calculateRiskScore($marketMetrics['risk_factors']);
        $profitability = $this->calculateProfitabilityScore($financials);
        
        return [
            'market_analysis' => [
                'growth_potential' => $marketMetrics['market_growth'] * 100 . '%',
                'competition_level' => $marketMetrics['competition_level'],
                'demand_rating' => $marketMetrics['demand_score'] . '/10'
            ],
            'risk_assessment' => [
                'overall_risk_score' => $riskScore,
                'risk_level' => $this->getRiskLevel($riskScore),
                'risk_factors' => $marketMetrics['risk_factors']
            ],
            'feasibility_score' => $this->calculateFeasibilityScore($profitability, $riskScore),
            'recommendation' => $this->generateRecommendation($profitability, $riskScore)
        ];
    }

    private function calculateRiskScore(array $riskFactors): float {
        return array_sum($riskFactors);
    }

    private function getRiskLevel(float $riskScore): string {
        if ($riskScore < 0.3) return 'Low';
        if ($riskScore < 0.6) return 'Medium';
        return 'High';
    }

    private function calculateProfitabilityScore(array $financials): float {
        $roi = $financials['roi_percentage'] / 100;
        $breakevenMonths = $financials['breakeven_months'];
        
        return ($roi * 0.6) + ((24 - min($breakevenMonths, 24)) / 24 * 0.4);
    }

    private function calculateFeasibilityScore(float $profitability, float $risk): float {
        return ($profitability * 0.7) - ($risk * 0.3);
    }

    private function generateRecommendation(float $profitability, float $risk): string {
        $feasibilityScore = $this->calculateFeasibilityScore($profitability, $risk);
        
        if ($feasibilityScore > 0.7) {
            return 'Highly Recommended - Strong market position with good profit potential';
        } elseif ($feasibilityScore > 0.4) {
            return 'Moderately Recommended - Consider with risk mitigation strategies';
        } else {
            return 'High Risk - Careful consideration and additional research recommended';
        }
    }
}

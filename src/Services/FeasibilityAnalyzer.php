<?php

namespace App\Services;

class FeasibilityAnalyzer {
    private const MARKET_SIZE_WEIGHTS = [
        'small' => 0.3,
        'medium' => 0.6,
        'large' => 0.9
    ];

    private const COMPETITION_WEIGHTS = [
        'low' => 0.8,
        'medium' => 0.5,
        'high' => 0.3
    ];

    private const RISK_WEIGHTS = [
        'low' => 0.2,
        'medium' => 0.5,
        'high' => 0.8
    ];

    public function analyze(array $data): array {
        $this->validateInput($data);

        $marketAnalysis = $this->analyzeMarket($data['market_size'], $data['competition_level']);
        $riskAssessment = $this->assessRisk($data['risk_level'], $data['risk_mitigation'], $data['initial_investment']);
        $feasibilityScore = $this->calculateFeasibility($marketAnalysis, $riskAssessment, $data);

        return [
            'success' => true,
            'market_analysis' => $marketAnalysis,
            'risk_assessment' => $riskAssessment,
            'feasibility_score' => $feasibilityScore,
            'recommendation' => $this->generateRecommendation($feasibilityScore)
        ];
    }

    private function validateInput(array $data): void {
        $required = ['market_size', 'competition_level', 'initial_investment', 
                    'expected_revenue', 'risk_level', 'risk_mitigation'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if ($data['initial_investment'] <= 0) {
            throw new \InvalidArgumentException("Initial investment must be greater than 0");
        }

        if ($data['risk_mitigation'] < 0) {
            throw new \InvalidArgumentException("Risk mitigation budget cannot be negative");
        }
    }

    private function analyzeMarket(string $marketSize, string $competitionLevel): array {
        $growthPotential = self::MARKET_SIZE_WEIGHTS[$marketSize] ?? 0.5;
        $competitionScore = self::COMPETITION_WEIGHTS[$competitionLevel] ?? 0.5;

        return [
            'growth_potential' => $growthPotential,
            'competition_level' => $competitionLevel,
            'demand_rating' => ($growthPotential + $competitionScore) / 2
        ];
    }

    private function assessRisk(string $riskLevel, float $riskMitigation, float $investment): array {
        $baseRiskScore = self::RISK_WEIGHTS[$riskLevel] ?? 0.5;
        $mitigationEffect = min(($riskMitigation / $investment), 0.5);
        $finalRiskScore = max(0, $baseRiskScore - $mitigationEffect);

        return [
            'risk_level' => $riskLevel,
            'overall_risk_score' => $finalRiskScore,
            'risk_factors' => [
                'market_risk' => $baseRiskScore,
                'financial_risk' => 1 - ($riskMitigation / $investment),
                'operational_risk' => $finalRiskScore * 0.8
            ]
        ];
    }

    private function calculateFeasibility(array $marketAnalysis, array $riskAssessment, array $data): float {
        $marketScore = $marketAnalysis['demand_rating'];
        $riskScore = 1 - $riskAssessment['overall_risk_score'];
        $roiScore = min(($data['expected_revenue'] * 12) / $data['initial_investment'], 1);
        
        return ($marketScore * 0.4) + ($riskScore * 0.3) + ($roiScore * 0.3);
    }

    private function generateRecommendation(float $score): string {
        if ($score > 0.7) {
            return "Highly feasible project with strong potential for success.";
        } elseif ($score > 0.4) {
            return "Moderately feasible project with some risks to consider.";
        }
        return "High-risk project that requires significant adjustments.";
    }
}

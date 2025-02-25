<?php
namespace Classes\Calculator\Business;

use Services\External\CustomsAPIService;

class CustomsDutyCalculator {
    private $customsAPI;
    private $rates = [
        'UAE' => [
            'duty_rate' => 0.05,
            'vat_rate' => 0.05,
            'processing_fee' => 500
        ],
        'PH' => [
            'duty_rate' => [
                'new' => 0.30,
                'used' => 0.15
            ],
            'vat_rate' => 0.12,
            'processing_fee' => 25000
        ]
    ];

    public function calculateDuty(array $vehicle, string $destination): array {
        $value = $vehicle['value'];
        $age = $vehicle['age'];
        $rates = $this->rates[$destination];

        $dutyRate = $destination === 'PH' ? 
            $this->getPhilippinesDutyRate($age) : 
            $rates['duty_rate'];

        $duty = $value * $dutyRate;
        $vat = ($value + $duty) * $rates['vat_rate'];

        return [
            'duty_amount' => $duty,
            'vat_amount' => $vat,
            'processing_fee' => $rates['processing_fee'],
            'total_duties' => $duty + $vat + $rates['processing_fee']
        ];
    }

    private function getPhilippinesDutyRate(int $age): float {
        return $age <= 1 ? 
            $this->rates['PH']['duty_rate']['new'] : 
            $this->rates['PH']['duty_rate']['used'];
    }
}

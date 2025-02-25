<?php

namespace App\Services;

use App\Services\ExchangeRateServiceInterface;
use App\Services\CacheManagerInterface;

class ExchangeRateService implements ExchangeRateServiceInterface
{
    private $cacheManager;
    private const CACHE_TTL = 3600; // 1 hour cache

    public function __construct(?CacheManagerInterface $cacheManager = null)
    {
        $this->cacheManager = $cacheManager;
    }

    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = $this->getRate($fromCurrency, $toCurrency);
        return round($amount * $rate, 2);
    }

    public function getRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "rate_{$fromCurrency}_{$toCurrency}";
        
        if ($this->cacheManager && $this->cacheManager->has($cacheKey)) {
            return (float)$this->cacheManager->get($cacheKey);
        }

        $rate = $this->fetchRateFromApi($fromCurrency, $toCurrency);
        
        if ($this->cacheManager) {
            $this->cacheManager->set($cacheKey, $rate, self::CACHE_TTL);
        }

        return $rate;
    }

    private function fetchRateFromApi(string $fromCurrency, string $toCurrency): float
    {
        // Current exchange rates for USD, AED, and PHP
        $rates = [
            'USD' => [
                'AED' => 3.67,  // 1 USD = 3.67 AED
                'PHP' => 55.98  // 1 USD = 55.98 PHP
            ],
            'AED' => [
                'USD' => 0.27,  // 1 AED = 0.27 USD
                'PHP' => 15.25  // 1 AED = 15.25 PHP
            ],
            'PHP' => [
                'USD' => 0.018, // 1 PHP = 0.018 USD
                'AED' => 0.066  // 1 PHP = 0.066 AED
            ]
        ];

        if (!isset($rates[$fromCurrency][$toCurrency])) {
            throw new \Exception("Exchange rate not available for {$fromCurrency} to {$toCurrency}");
        }

        return $rates[$fromCurrency][$toCurrency];
    }

    // Helper method to validate currency codes
    private function validateCurrency(string $currency): bool
    {
        $validCurrencies = ['USD', 'AED', 'PHP'];
        return in_array($currency, $validCurrencies);
    }
}

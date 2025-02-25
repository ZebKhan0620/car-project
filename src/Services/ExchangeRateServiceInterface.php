<?php
namespace App\Services;

interface ExchangeRateServiceInterface
{
    /**
     * Convert an amount from one currency to another
     *
     * @param float $amount The amount to convert
     * @param string $fromCurrency Source currency code (e.g., 'USD', 'AED', 'PHP')
     * @param string $toCurrency Target currency code (e.g., 'USD', 'AED', 'PHP')
     * @return float The converted amount
     * @throws \Exception If currency conversion fails
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): float;

    /**
     * Get exchange rate between two currencies
     *
     * @param string $fromCurrency Source currency code (e.g., 'USD', 'AED', 'PHP')
     * @param string $toCurrency Target currency code (e.g., 'USD', 'AED', 'PHP')
     * @return float The exchange rate
     * @throws \Exception If exchange rate is not available
     */
    public function getRate(string $fromCurrency, string $toCurrency): float;
}
<?php

class FoxypayConverter {

    private $exchangeRates = [];

    public function __construct() {
        $this->exchangeRates = [
            "KZT" => [
                "USD" => (new CurrencyConverter())->getCurrency("KZT", "USD"),
                "EUR" => (new CurrencyConverter())->getCurrency("KZT", "EUR"),
                "UAH" => (new CurrencyConverter())->getCurrency("KZT", "UAH"),
            ]
        ];
    }

    public function getExchangeRate($fromCurrency, $toCurrency) {
        // Если валюты совпадают, возвращаем коэффициент 1
        if ($fromCurrency === $toCurrency) {
            return 1; 
        }

        // Возвращаем курс обмена или null, если он не найден
        if (isset($this->exchangeRates[$fromCurrency][$toCurrency])) {
            return $this->exchangeRates[$fromCurrency][$toCurrency];
        } else {
            return null; 
        }
    }

    public function convertCurrency($amountInCents, $fromCurrency, $toCurrency) {
        $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency);

        // Если курс не найден, выбрасываем исключение
        if ($exchangeRate === null) {
            throw new Exception("Курс обмена с $fromCurrency на $toCurrency не найден.");
        }

        // Проводим конвертацию (умножаем сумму на курс)
        return ($amountInCents / 100) * $exchangeRate; 
    }
}

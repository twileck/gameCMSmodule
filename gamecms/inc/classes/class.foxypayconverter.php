<?php

class FoxypayConverter {

    private $exchangeRates = [];
    public function __construct($exchangeRates){
        if($exchangeRates == 'KZT'){
            $this->exchangeRates = [
                "KZT" => [
                    "USD" => (new CurrencyConverter())->convertCurrency(1, "KZT", "USD"),
                    "EUR" => (new CurrencyConverter())->convertCurrency(1, "KZT", "EUR"),
                    "UAH" => (new CurrencyConverter())->convertCurrency(1, "KZT", "UAH"),
                ]
            ];
        }
    }

    public function getExchangeRate($fromCurrency, $toCurrency) {
        // Если курсы совпадают, вернуть 1
        if ($fromCurrency === $toCurrency) {
            return 1; // Конвертация не требуется
        }
        
        if (isset($this->exchangeRates[$fromCurrency][$toCurrency])) {
            return $this->exchangeRates[$fromCurrency][$toCurrency];
        } else {
            return null; // Возвращаем null, если курс не найден
        }
    }
    
    public function convertCurrency($amountInCents, $fromCurrency, $toCurrency) {
        $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency);

        // Если валюты совпадают, просто возвращаем сумму без изменений
        if ($fromCurrency === $toCurrency) {
            return $amountInCents / 100; // Если валюты совпадают, возвращаем сумму в единицах валюты
        }
    
        // В противном случае проводим конвертацию
        if ($exchangeRate) {
            return ($amountInCents / 100) * $exchangeRate; // Умножаем сумму на обменный курс
        }
        return null; // Если курс не найден
    }     
}

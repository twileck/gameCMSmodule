<?php

class FoxypayConverter {

    private $exchangeRates = [];
    public function __construct($exchangeRates){
        if($exchangeRates == 'RUB'){
            $this->exchangeRates = [
                "RUB" => [
                    "KZT" => (new CurrencyConverter())->getCurrencyRUB("KZT", 2),
                    "USD" => (new CurrencyConverter())->getCurrencyRUB("USD", 2),
                    "EUR" => (new CurrencyConverter())->getCurrencyRUB("EUR", 2),
                    "UAH" => (new CurrencyConverter())->getCurrencyRUB("UAH", 1),
                ]
            ];
        }
    }

    public function getExchangeRate($fromCurrency, $toCurrency) {
        // Если курсы совпадают, вернуть 1
        if ($fromCurrency === $toCurrency) {
            return null; // Или можно просто вернуть null, так как это будет означать, что конвертация не требуется
        }
        
        // Возвращаем курс обмена между указанными валютами или null, если курс не найден
        return $this->exchangeRates[$fromCurrency][$toCurrency] ?? null;
    }
    
    public function convertCurrency($amountInCents, $fromCurrency, $toCurrency) {
        $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency);

        // Если валюты совпадают, просто возвращаем сумму без изменений
        if ($fromCurrency === $toCurrency) {
            return $amountInCents / 100; // Если валюты совпадают, возвращаем сумму в единицах валюты
        }
    
        // В противном случае проводим конвертацию
        return ($amountInCents / 100) * $exchangeRate; // Умножаем сумму на обменный курс
    }     
}


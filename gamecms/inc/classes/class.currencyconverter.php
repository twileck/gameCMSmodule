<?php

class CurrencyConverter {
    private $apiUrl = 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json';

    public function getExchangeRates() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($ch);
        curl_close($ch);

        if ($json === false) {
            return false; // Не удалось получить данные
        }

        $data = json_decode($json, true);
        if (!$data) {
            return false; // Некорректный формат JSON
        }

        // Возвращаем только курсы валют
        return $data;
    }

    public function convertCurrency($amount, $fromCurrencyCode, $toCurrencyCode) {
        $exchangeRates = $this->getExchangeRates();
        if (!$exchangeRates) {
            return false; // Не удалось получить курсы валют
        }

        $fromRate = $this->findCurrencyRate($exchangeRates, $fromCurrencyCode);
        $toRate = $this->findCurrencyRate($exchangeRates, $toCurrencyCode);

        if ($fromRate === false || $toRate === false) {
            return false; // Одна из валют не найдена
        }

        // Конвертируем сумму из одной валюты в другую
        $convertedAmount = bcmul(bcdiv($amount * $fromRate, $toRate, 2), 100);

        // Округляем, если сумма дробная
        if (!is_int($convertedAmount)) {
            $convertedAmount = round($convertedAmount);
        }

        return $convertedAmount;
    }

    private function findCurrencyRate($exchangeRates, $currencyCode) {
        foreach ($exchangeRates as $currency) {
            if ($currency['cc'] === $currencyCode) {
                return $currency['rate'];
            }
        }
        return false; // Валюта не найдена
    }

    /*
     * Получение курса для рубля
     * @param string $currency_code = "USD" - Код валюты
     * @param int $format = 3 - Сколько знаков после запятой
     * @return int - Возвращаем нае значение
     */
    public function getCurrencyRUB($currency_code, $format)
    {
        $date = date('d.m.Y'); // Текущая дата

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.cbr.ru/scripts/XML_daily.asp?date_req=' . $date);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $out = curl_exec($ch);

        curl_close($ch);

        $content_currency = simplexml_load_string($out);
        return number_format(str_replace(',', '.', $content_currency->xpath('Valute[CharCode="' . $currency_code . '"]')[0]->Value), $format);
    }
}

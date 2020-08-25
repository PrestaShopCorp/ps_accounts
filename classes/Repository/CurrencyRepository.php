<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Currency;

class CurrencyRepository
{
    public function getCurrenciesIsoCodes()
    {
        $currencies = Currency::getCurrencies();

        return array_map(function ($currency) {
            return $currency['iso_code'];
        }, $currencies);
    }

    public function getDefaultCurrencyIsoCode()
    {
        return Currency::getDefaultCurrency()->iso_code;
    }
}

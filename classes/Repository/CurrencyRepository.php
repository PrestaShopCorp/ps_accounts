<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Currency;

class CurrencyRepository
{
    /**
     * @return array
     */
    public function getCurrenciesIsoCodes()
    {
        $currencies = Currency::getCurrencies();

        return array_map(function ($currency) {
            return $currency['iso_code'];
        }, $currencies);
    }

    /**
     * @return string
     */
    public function getDefaultCurrencyIsoCode()
    {
        $currency = Currency::getDefaultCurrency();

        return $currency instanceof Currency ? $currency->iso_code : '';
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Configuration;
use Currency;
use Language;

class ServerInformationRepository
{
    public function getServerInformation()
    {
        $defaultLang = Language::getLanguage(Configuration::get('PS_LANG_DEFAULT'));
        $defaultCurrency = Currency::getDefaultCurrency();

        $allLang = Language::getLanguages();
        $languages = array();
        foreach ($allLang as $lang) {
            $languages[] = $lang['iso_code'];
        }

        $currencies = Currency::getCurrencies();

        $currencyIsos = [];

        foreach ($currencies as $currency) {
            $currencyIsos[] = $currency['iso_code'];
        }
        return [
            'id' => 1,
            'collection' => 'info',
            'properties' => [
                "timestamp" => time(),
                "summary" => [
                    "CMS" => [
                        "name" => "prestashop",
                        "version" => _PS_VERSION_
                    ],
                    "url_is_simplified" => (bool) Configuration::get('PS_REWRITING_SETTINGS'),
                    "cart_is_persistent" => (bool) Configuration::get('PS_CART_FOLLOWING'),
                    "defaultLanguage" => $defaultLang['iso_code'],
                    "languages" => $languages,
                    "defaultCurrency" => $defaultCurrency->iso_code,
                    "currencies" => $currencyIsos,
                    "timezone" => Configuration::get('PS_TIMEZONE'),
                    "PHP" => phpversion(),
                    "HTTPserver" => $_SERVER['SERVER_SOFTWARE']
                ]
            ],
        ];
    }
}

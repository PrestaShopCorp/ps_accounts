<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Configuration;
use Currency;
use Language;

class ServerInformationRepository
{
    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;
    /**
     * @var LanguageRepository
     */
    private $languageRepository;
    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    public function __construct(
        CurrencyRepository $currencyRepository,
        LanguageRepository $languageRepository,
        ConfigurationRepository $configurationRepository
    ) {
        $this->currencyRepository = $currencyRepository;
        $this->languageRepository = $languageRepository;
        $this->configurationRepository = $configurationRepository;
    }

    public function getServerInformation()
    {
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
                    "url_is_simplified" => (bool) $this->configurationRepository->get('PS_REWRITING_SETTINGS'),
                    "cart_is_persistent" => (bool) $this->configurationRepository->get('PS_CART_FOLLOWING'),
                    "defaultLanguage" => $this->languageRepository->getDefaultLanguageIsoCode(),
                    "languages" => $this->languageRepository->getLanguagesIsoCodes(),
                    "defaultCurrency" => $this->currencyRepository->getDefaultCurrencyIsoCode(),
                    "currencies" => $this->currencyRepository->getCurrenciesIsoCodes(),
                    "timezone" => $this->configurationRepository->get('PS_TIMEZONE'),
                    "PHP" => phpversion(),
                    "HTTPserver" => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : ''
                ]
            ],
        ];
    }
}

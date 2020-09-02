<?php

namespace PrestaShop\Module\PsAccounts\Repository;

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

    /**
     * @return array
     */
    public function getServerInformation()
    {
        return [
            'id' => '1',
            'collection' => 'shops',
            'properties' => [
                'timestamp' => time(),
                'cms_name' => 'prestashop',
                'cms_version' => _PS_VERSION_,
                'url_is_simplified' => (bool) $this->configurationRepository->get('PS_REWRITING_SETTINGS'),
                'cart_is_persistent' => (bool) $this->configurationRepository->get('PS_CART_FOLLOWING'),
                'default_language' => $this->languageRepository->getDefaultLanguageIsoCode(),
                'languages' => implode(';', $this->languageRepository->getLanguagesIsoCodes()),
                'default_currency' => $this->currencyRepository->getDefaultCurrencyIsoCode(),
                'currencies' => implode(';', $this->currencyRepository->getCurrenciesIsoCodes()),
                'timezone' => $this->configurationRepository->get('PS_TIMEZONE'),
                'php_version' => phpversion(),
                'http_server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
            ],
        ];
    }
}

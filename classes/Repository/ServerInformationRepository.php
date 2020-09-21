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
            [
                'id' => '1',
                'collection' => 'shops',
                'properties' => [
                    'created_at' => date(DATE_ATOM),
                    'cms_version' => _PS_VERSION_,
                    'url_is_simplified' => $this->configurationRepository->get('PS_REWRITING_SETTINGS') == '1',
                    'cart_is_persistent' => $this->configurationRepository->get('PS_CART_FOLLOWING') == '1',
                    'default_language' => $this->languageRepository->getDefaultLanguageIsoCode(),
                    'languages' => implode(';', $this->languageRepository->getLanguagesIsoCodes()),
                    'default_currency' => $this->currencyRepository->getDefaultCurrencyIsoCode(),
                    'currencies' => implode(';', $this->currencyRepository->getCurrenciesIsoCodes()),
                    'weight_unit' => $this->configurationRepository->get('PS_WEIGHT_UNIT'),
                    'timezone' => $this->configurationRepository->get('PS_TIMEZONE'),
                    'is_order_return_enabled' => $this->configurationRepository->get('PS_ORDER_RETURN') == '1',
                    'order_return_nb_days' => (int) $this->configurationRepository->get('PS_ORDER_RETURN_NB_DAYS'),
                    'php_version' => phpversion(),
                    'http_server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
                ],
            ],
        ];
    }
}

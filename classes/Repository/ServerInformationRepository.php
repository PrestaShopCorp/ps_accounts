<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use Db;
use Exception;
use Language;
use PrestaShop\AccountsAuth\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\PrestaShop\Adapter\Entity\Module;
use PrestaShopDatabaseException;

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
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Db
     */
    private $db;
    /**
     * @var ArrayFormatter
     */
    private $arrayFormatter;

    public function __construct(
        CurrencyRepository $currencyRepository,
        LanguageRepository $languageRepository,
        ConfigurationRepository $configurationRepository,
        Context $context,
        Db $db,
        ArrayFormatter $arrayFormatter
    ) {
        $this->currencyRepository = $currencyRepository;
        $this->languageRepository = $languageRepository;
        $this->configurationRepository = $configurationRepository;
        $this->context = $context;
        $this->db = $db;
        $this->arrayFormatter = $arrayFormatter;
    }

    /**
     * @param null $langIso
     *
     * @return array
     */
    public function getServerInformation($langIso = null)
    {
        $langId = $langIso != null ? (int)Language::getIdByIso($langIso) : null;

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
                    'order_return_nb_days' => (int)$this->configurationRepository->get('PS_ORDER_RETURN_NB_DAYS'),
                    'php_version' => phpversion(),
                    'http_server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
                    'url' => $this->context->link->getPageLink('index', null, $langId),
                    'ssl' => $this->configurationRepository->get('PS_SSL_ENABLED') == '1',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getHealthCheckData()
    {
        $tokenValid = true;
        $tablesFetchedSuccessfully = true;
        $allTablesInstalled = true;
        $errors = [];

        $psAccountsService = new PsAccountsService();

        try {
            $psAccountsService->getOrRefreshToken();
        } catch (Exception $e) {
            $tokenValid = false;
        }

        $requiredTables = [
            _DB_PREFIX_ . 'accounts_type_sync',
            _DB_PREFIX_ . 'accounts_sync',
        ];

        try {
            $tables = $this->db->executeS("
            SELECT t.TABLE_NAME AS table_name
            FROM   INFORMATION_SCHEMA.TABLES AS t
            WHERE  t.TABLE_TYPE = 'BASE TABLE'
            AND  t.TABLE_SCHEMA = '" . _DB_NAME_ . "'
            ");

            if (empty($tables)) {
                $tablesFetchedSuccessfully = false;
            } else {
                $tablesFormatted = $this->arrayFormatter->formatValueArray($tables, 'table_name');

                foreach ($requiredTables as $requiredTable) {
                    if (!in_array($requiredTable, $tablesFormatted)) {
                        $allTablesInstalled = false;
                        $errors[] = "$requiredTable is missing";
                    }
                }
            }
        } catch (PrestaShopDatabaseException $e) {
            $tablesFetchedSuccessfully = false;
        }

        $module = Module::getInstanceByName('ps_accounts');

        return [
            'prestashop_version' => _PS_VERSION_,
            'ps_accounts_version' => $module->version,
            'php_version' => phpversion(),
            'firebase_token_valid' => $tokenValid,
            'tables_fetched_successfully' => $tablesFetchedSuccessfully,
            'tables_installed' => $allTablesInstalled,
            'env' => [
                'EVENT_BUS_PROXY_API_URL' => isset($_ENV['EVENT_BUS_PROXY_API_URL']) ? $_ENV['EVENT_BUS_PROXY_API_URL'] : null,
                'EVENT_BUS_SYNC_API_URL' => isset($_ENV['EVENT_BUS_SYNC_API_URL']) ? $_ENV['EVENT_BUS_SYNC_API_URL'] : null,
            ],
            'errors' => $errors,
        ];
    }
}

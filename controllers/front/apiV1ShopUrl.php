<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ps_AccountsApiV1ShopUrlModuleFrontController extends AbstractShopRestController
{
    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * ps_AccountsApiV1ShopUrlModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = $this->module->getService(ConfigurationRepository::class);
    }

    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return array
     *
     * @throws Exception
     */
    public function show($shop, array $payload)
    {
        return [
            'domain' => $shop->domain,
            'domain_ssl' => $shop->domain_ssl,
            'ssl_activated' => $this->configuration->sslEnabled(),
        ];
    }
}

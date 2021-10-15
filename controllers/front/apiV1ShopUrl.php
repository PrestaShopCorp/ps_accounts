<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ps_AccountsApiV1ShopUrlModuleFrontController extends AbstractShopRestController
{
    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * ps_AccountsApiV1ShopUrlModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = $this->module->getService(ConfigurationRepository::class);
        $this->shopProvider = $this->module->getService(ShopProvider::class);
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
        $shopData = $this->shopProvider->formatShopData((array) \Shop::getShop($shop->id));

        return [
            'domain' => $shopData['domain'],
            'domain_ssl' => $shopData['domainSsl'],
            'physical_uri' => $shopData['physicalUri'],
            'ssl_activated' => $this->configuration->sslEnabled(),
        ];
    }
}

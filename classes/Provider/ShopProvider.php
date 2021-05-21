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

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Context\ShopContext;

class ShopProvider
{
    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var Link
     */
    private $link;

    /**
     * ShopProvider constructor.
     *
     * @param ShopContext $shopContext
     * @param Link $link
     */
    public function __construct(
        ShopContext $shopContext,
        Link $link
    ) {
        $this->shopContext = $shopContext;
        $this->link = $link;
    }

    /**
     * @param string $psxName
     *
     * @return array
     *
     * @throws \PrestaShopException
     */
    public function getCurrentShop($psxName = '')
    {
        $shop = \Shop::getShop($this->shopContext->getContext()->shop->id);

        $configuration = $this->shopContext->getConfiguration();

        return [
            'id' => (string) $shop['id_shop'],
            'name' => $shop['name'],
            'domain' => $shop['domain'],
            'domainSsl' => $shop['domain_ssl'],
            'multishop' => $this->shopContext->isMultishopActive(),
            'moduleName' => $psxName,
            'psVersion' => _PS_VERSION_,

            // LinkAccount
            'publicKey' => $configuration->getAccountsRsaPublicKey(),
            'employeeId' => (int) $configuration->getEmployeeId() ?: null,

            'url' => $this->link->getAdminLink(
                'AdminModules',
                true,
                [],
                [
                    'configure' => $psxName,
                    'setShopContext' => 's-' . $shop['id_shop'],
                ]
            ),
        ];
    }

    /**
     * @param string $psxName
     *
     * @return array
     *
     * @throws \PrestaShopException
     */
    public function getShopsTree($psxName)
    {
        $shopList = [];

//        if (true === $this->shopContext->isShopContext()) {
//            return $shopList;
//        }

        $configuration = $this->shopContext->getConfiguration();

        foreach (\Shop::getTree() as $groupId => $groupData) {
            $shops = [];
            foreach ($groupData['shops'] as $shopId => $shopData) {
                $configuration->setShopId($shopId);

                $shops[] = [
                    'id' => (string) $shopId,
                    'name' => $shopData['name'],
                    'domain' => $shopData['domain'],
                    'domainSsl' => $shopData['domain_ssl'],

                    // LinkAccount
                    'publicKey' => $configuration->getAccountsRsaPublicKey(),
                    'employeeId' => (int) $configuration->getEmployeeId() ?: null,

                    'url' => $this->link->getAdminLink(
                        'AdminModules',
                        true,
                        [],
                        [
                            'configure' => $psxName,
                            'setShopContext' => 's-' . $shopId,
                        ]
                    ),
                ];
            }

            $shopList[] = [
                'id' => (string) $groupId,
                'name' => $groupData['name'],
                'shops' => $shops,
                'multishop' => $this->shopContext->isMultishopActive(),
                'moduleName' => $psxName,
                'psVersion' => _PS_VERSION_,
            ];
        }

        $configuration->setShopId($this->shopContext->getContext()->shop->id);

        return $shopList;
    }

    /**
     * @return ShopContext
     */
    public function getShopContext()
    {
        return $this->shopContext;
    }
}

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
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

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
     * @param array $shopData
     * @param string $psxName
     *
     * @return array
     *
     * @throws \Exception
     */
    public function formatShopData($shopData, $psxName = '')
    {
        $configuration = $this->shopContext->getConfiguration();
        $userToken = $this->shopContext->getUserToken();

        $shopId = $configuration->getShopId();

        $configuration->setShopId($shopData['id_shop']);

        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $module->getService(ShopLinkAccountService::class);

        /** @var RsaKeysProvider $rsaKeyProvider */
        $rsaKeyProvider = $module->getService(RsaKeysProvider::class);

        $data = [
            'id' => (string) $shopData['id_shop'],
            'name' => $shopData['name'],
            'domain' => $shopData['domain'],
            'domainSsl' => $shopData['domain_ssl'],
            'physicalUri' => $this->getShopPhysicalUri($shopData['id_shop']),

            // LinkAccount
            'uuid' => $configuration->getShopUuid() ?: null,
            'publicKey' => $rsaKeyProvider->getOrGenerateAccountsRsaPublicKey() ?: null,
            'employeeId' => (int) $configuration->getEmployeeId() ?: null,
            'user' => [
                'email' => $userToken->getTokenEmail() ?: null,
                'uuid' => $userToken->getTokenUuid() ?: null,
                'emailIsValidated' => $userToken->getTokenEmailVerified(),
            ],

            'url' => $this->link->getAdminLink(
                'AdminModules',
                true,
                [],
                [
                    'configure' => $psxName,
                    'setShopContext' => 's-' . $shopData['id_shop'],
                ]
            ),
            'isLinkedV4' => $shopLinkAccountService->isAccountLinkedV4(),
        ];

        $configuration->setShopId($shopId);

        return $data;
    }

    // TODO Add public function to get main shop

    /**
     * @param string $psxName
     *
     * @return array
     *
     * @throws \PrestaShopException
     */
    public function getCurrentShop($psxName = '')
    {
        $data = $this->formatShopData((array) \Shop::getShop($this->shopContext->getContext()->shop->id), $psxName);

        return array_merge($data, [
            'multishop' => $this->shopContext->isMultishopActive(),
            'moduleName' => $psxName,
            'psVersion' => _PS_VERSION_,
        ]);
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

        foreach (\Shop::getTree() as $groupId => $groupData) {
            $shops = [];
            foreach ($groupData['shops'] as $shopId => $shopData) {
                $data = $this->formatShopData((array) $shopData, $psxName);

                $shops[] = array_merge($data, [
                    'multishop' => $this->shopContext->isMultishopActive(),
                    'moduleName' => $psxName,
                    'psVersion' => _PS_VERSION_,
                ]);
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

        return $shopList;
    }

    /**
     * @param string $psxName
     * @param int $employeeId
     *
     * @return array
     *
     * @throws \PrestaShopException
     */
    public function getUnlinkedShops($psxName, $employeeId)
    {
        $shopTree = $this->getShopsTree($psxName);
        $shops = [];

        switch ($this->getShopContext()->getShopContext()) {
            case \Shop::CONTEXT_ALL:
                $shops = array_reduce($shopTree, function ($carry, $shopGroup) {
                    return array_merge($carry, $shopGroup['shops']);
                }, []);
                break;
            case \Shop::CONTEXT_GROUP:
                $shops = array_reduce($shopTree, function ($carry, $shopGroup) {
                    if ($shopGroup['id'] != $this->getShopContext()->getShopContextId()) {
                        return $carry;
                    }

                    return array_merge($carry, $shopGroup['shops']);
                }, []);
                break;
            case \Shop::CONTEXT_SHOP:
                $shops = [$this->getCurrentShop($psxName)];
                break;
        }

        $unlinkedShops = array_filter($shops, function ($shop) {
            return $shop['uuid'] === null || ($shop['uuid'] && $shop['isLinkedV4']);
        });

        return array_map(function ($shop) use ($employeeId) {
            $shop['employeeId'] = (string) $employeeId;

            return $shop;
        }, $unlinkedShops);
    }

    /**
     * @return ShopContext
     */
    public function getShopContext()
    {
        return $this->shopContext;
    }

    /**
     * @param int $shopId
     *
     * @return false|string
     */
    private function getShopPhysicalUri($shopId)
    {
        return \Db::getInstance()->getValue(
            'SELECT physical_uri FROM ' . _DB_PREFIX_ . 'shop_url WHERE id_shop=' . (int) $shopId . ' AND main=1'
        );
    }
}

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
use PrestaShop\Module\PsAccounts\Domain\Shop\Dto\Shop;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Association;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\PublicKey;

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
     * @param array $shopData data returned by \Shop::getShop
     * @param string $psxName
     *
     * @return Shop
     *
     * @throws \Exception
     */
    public function formatShopData(array $shopData, string $psxName = ''): Shop
    {
        return $this->getShopContext()->execInShopContext($shopData['id_shop'], function () use ($shopData, $psxName) {
            /** @var \Ps_accounts $module */
            $module = \Module::getInstanceByName('ps_accounts');

            /** @var Association $association */
            $association = $module->getService(Association::class);
            $shopSession = $association->getShopSession();
            $ownerSession = $association->getOwnerSession();

            /** @var PublicKey $rsaKeyProvider */
            $rsaKeyProvider = $module->getService(PublicKey::class);

            return new Shop([
                'id' => (string) $shopData['id_shop'],
                'name' => $shopData['name'],
                'domain' => $shopData['domain'],
                'domainSsl' => $shopData['domain_ssl'],
                'physicalUri' => $this->getShopPhysicalUri($shopData['id_shop']),
                'virtualUri' => $this->getShopVirtualUri($shopData['id_shop']),
                'frontUrl' => $this->getShopUrl($shopData),

                // LinkAccount
                'uuid' => $shopSession->getToken()->getUuid() ?: null,
                'publicKey' => $rsaKeyProvider->getOrGeneratePublicKey() ?: null,
                'employeeId' => (int) $ownerSession->getEmployeeId() ?: null,
                'user' => [
                    'email' => $ownerSession->getToken()->getEmail() ?: null,
                    'uuid' => $ownerSession->getToken()->getUuid() ?: null,
                    'emailIsValidated' => $ownerSession->isEmailVerified(),
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
                'isLinkedV4' => $association->isLinkedV4(),
            ]);
        });
    }

    /**
     * @param string $psxName
     *
     * @return array
     *
     * @throws \PrestaShopException|\Exception
     */
    public function getCurrentShop(string $psxName = ''): array
    {
        $data = $this->formatShopData((array) \Shop::getShop($this->shopContext->getContext()->shop->id), $psxName);

        return array_merge((array) $data, [
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
     * @throws \PrestaShopException|\Exception
     */
    public function getShopsTree(string $psxName): array
    {
        $shopList = [];

        foreach (\Shop::getTree() as $groupId => $groupData) {
            $shops = [];
            foreach ($groupData['shops'] as $shopId => $shopData) {
                $data = $this->formatShopData((array) $shopData, $psxName);

                $shops[] = array_merge((array) $data, [
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
    public function getUnlinkedShops(string $psxName, int $employeeId): array
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
    public function getShopContext(): ShopContext
    {
        return $this->shopContext;
    }

    /**
     * @param int $shopId
     *
     * @return false|string
     */
    private function getShopPhysicalUri(int $shopId)
    {
        return \Db::getInstance()->getValue(
            'SELECT physical_uri FROM ' . _DB_PREFIX_ . 'shop_url WHERE id_shop=' . (int) $shopId . ' AND main=1'
        );
    }

    /**
     * @param int $shopId
     *
     * @return false|string
     */
    private function getShopVirtualUri(int $shopId)
    {
        return \Db::getInstance()->getValue(
            'SELECT virtual_uri FROM ' . _DB_PREFIX_ . 'shop_url WHERE id_shop=' . (int) $shopId . ' AND main=1'
        );
    }

    /**
     * @param array $shopData
     *
     * @return string|null
     */
    private function getShopUrl(array $shopData): ?string
    {
        if (!$shopData['domain']) {
            return null;
        }

        return
            ($shopData['domain_ssl'] ? 'https://' : 'http://') .
            ($shopData['domain_ssl'] ?: $shopData['domain']) .
            $shopData['uri'];
    }
}

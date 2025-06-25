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

use PrestaShop\Module\PsAccounts\Account\Dto\Shop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\ShopUrl;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;

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
     * @var StatusManager
     */
    private $shopStatus;

    /**
     * @var OAuth2Service
     */
    private $oAuth2Service;

    /**
     * ShopProvider constructor.
     *
     * @param ShopContext $shopContext
     * @param Link $link
     * @param StatusManager $shopStatus
     */
    public function __construct(
        ShopContext $shopContext,
        Link $link,
        StatusManager $shopStatus,
        OAuth2Service $oAuth2Service
    ) {
        $this->shopContext = $shopContext;
        $this->link = $link;
        $this->shopStatus = $shopStatus;
        $this->oAuth2Service = $oAuth2Service;
    }

    /**
     * @param array $shopData data returned by \Shop::getShop
     * @param string $psxName
     * @param bool $refreshTokens
     *
     * @return Shop
     *
     * @throws \Exception
     */
    public function formatShopData(array $shopData, $psxName = '', $refreshTokens = true)
    {
        return $this->getShopContext()->execInShopContext($shopData['id_shop'], function () use ($shopData, $refreshTokens) {
            /** @var \Ps_accounts $module */
            $module = \Module::getInstanceByName('ps_accounts');

            /** @var StatusManager $shopStatus */
            $shopStatus = $module->getService(StatusManager::class);

            /** @var OwnerSession $ownerSession */
            $ownerSession = $module->getService(OwnerSession::class);

            $shopId = $shopData['id_shop'];

            $shop = new Shop([
                'id' => (string) $shopId,
                'name' => $shopData['name'],
                'domain' => $shopData['domain'],
                'domainSsl' => $shopData['domain_ssl'],
                'physicalUri' => $this->getShopPhysicalUri($shopId),
                'virtualUri' => $this->getShopVirtualUri($shopId),
                // FIXME: we should probably use this :
                //'frontUrl' => $this->link->getLink()->getBaseLink(),
                'frontUrl' => $this->getShopUrl($shopData),

                // LinkAccount
                'uuid' => $shopStatus->getCloudShopId() ?: null,
                'publicKey' => '[deprecated]',
                'employeeId' => 0, //(int) $shopIdentity->getEmployeeId() ?: null,
                'user' => [
                    'email' => $shopStatus->getPointOfContactEmail() ?: null,
                    'uuid' => $shopStatus->getPointOfContactUuid() ?: null,
                    'emailIsValidated' => null,
                ],
                'url' => $this->link->getDashboardLink(),
                'isLinkedV4' => null,
                'unlinkedAuto' => false,
            ]);

            if ($refreshTokens) {
                $shop->user->emailIsValidated = $ownerSession->isEmailVerified();
                $shop->isLinkedV4 = false; //$shopIdentity->existsV4();
            }

            return $shop;
        });
    }

    /**
     * @param string $psxName
     *
     * @return array
     *
     * @throws \PrestaShopException|\Exception
     */
    public function getCurrentShop($psxName = 'ps_accounts')
    {
        $shop = $this->formatShopData((array) \Shop::getShop($this->shopContext->getContext()->shop->id), $psxName);

        return array_merge($shop->jsonSerialize(), [
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
     * @throws \Exception
     */
    public function getShopsTree($psxName)
    {
        $shopList = [];

        foreach (\Shop::getTree() as $groupId => $groupData) {
            $shops = [];
            foreach ($groupData['shops'] as $shopId => $shopData) {
                $shop = $this->formatShopData((array) $shopData, $psxName);

                $shops[] = array_merge($shop->jsonSerialize(), [
                    'multishop' => $this->shopContext->isMultishopActive(),
                    'moduleName' => $psxName,
                    'psVersion' => _PS_VERSION_,
                    'moduleVersion' => \Ps_accounts::VERSION,
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

    /**
     * @param int $shopId
     *
     * @return false|string
     */
    private function getShopVirtualUri($shopId)
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
    private function getShopUrl($shopData)
    {
        if (!isset($shopData['domain'])) {
            return null;
        }

        return
            ($shopData['domain_ssl'] ? 'https://' : 'http://') .
            ($shopData['domain_ssl'] ?: $shopData['domain']) .
            $shopData['uri'];
    }

    /**
     * @param int $shopId
     *
     * @return string|null
     */
    public function getFrontendUrl($shopId)
    {
        return $this->getShopUrl((array) \Shop::getShop($shopId));
    }

    /**
     * @param int $shopId
     *
     * @return string|null
     */
    public function getBackendUrl($shopId)
    {
        $shop = new \Shop($shopId);

        if (!$shop->id) {
            return null;
        }

        $boBaseUri = ($shop->domain_ssl ? 'https://' : 'http://') .
            ($shop->domain_ssl ?: $shop->domain) . $shop->physical_uri;

        // FIXME: throw exception in wrong context
        // FIXME: unit tests
        $adminPath = defined('_PS_ADMIN_DIR_') ? basename(_PS_ADMIN_DIR_) : '';

        return rtrim($boBaseUri, '/') . '/' . $adminPath;
    }

    /**
     * @param int $shopId
     *
     * @return ShopUrl
     */
    public function getUrl($shopId)
    {
        $backOfficeUrl = $this->getBackendUrl($shopId);
        $frontendUrl = rtrim($this->getFrontendUrl($shopId), '/');

        return new ShopUrl($backOfficeUrl, $frontendUrl, $shopId);
    }

    /**
     * @param string|null $groupId
     * @param string|null $shopId
     * @param bool $refresh
     *
     * @return array
     */
    public function getShops($groupId = null, $shopId = null, $refresh = false)
    {
        $shopList = [];
        foreach (\Shop::getTree() as $groupData) {
            if ($groupId !== null && $groupId !== $groupData['id']) {
                continue;
            }

            $shops = [];
            foreach ($groupData['shops'] as $shopData) {
                if ($shopId !== null && $shopId !== $shopData['id_shop']) {
                    continue;
                }

                $this->getShopContext()->execInShopContext($shopData['id_shop'], function () use (&$shops, $shopData, $refresh) {
                    $shopUrl = $this->getUrl((int) $shopData['id_shop']);
                    $shopStatus = $this->shopStatus->getStatus($refresh);
                    $identifyUrl = $this->oAuth2Service->getOAuth2Client()->getRedirectUri([
                        'action' => 'identifyPointOfContact',
                    ]);

                    $shops[] = [
                        'id' => (int) $shopData['id_shop'],
                        'name' => $shopData['name'],
                        'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
                        'frontendUrl' => $shopUrl->getFrontendUrl(),
                        'identifyUrl' => $identifyUrl,
                        'shopStatus' => $shopStatus,
                    ];
                });
            }

            $shopList[] = [
                'id' => (int) $groupData['id'],
                'name' => $groupData['name'],
                'shops' => $shops,
            ];
        }

        return $shopList;
    }
}

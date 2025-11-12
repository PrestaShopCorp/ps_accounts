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

namespace PrestaShop\Module\PsAccounts\Adapter;

use PrestaShop\Module\PsAccounts\Context\ShopContext;

/**
 * Link adapter
 */
class Link
{
    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * Link object
     *
     * @var \Link
     */
    private $link;

    /**
     * @param ShopContext $shopContext
     * @param \Link|null $link
     */
    public function __construct(
        ShopContext $shopContext,
        \Link $link = null
    ) {
        if (null === $link) {
            $link = new \Link();
        }
        $this->shopContext = $shopContext;
        $this->link = $link;
    }

    /**
     * Adapter for getAdminLink from prestashop link class
     *
     * @param string $controller controller name
     * @param bool $withToken include or not the token in the url
     * @param array $sfRouteParams
     * @param array $params
     * @param int|null $shopId generate uri for a specific multishop id
     *
     * @return string
     */
    public function getAdminLink($controller, $withToken = true, $sfRouteParams = [], $params = [], $shopId = null)
    {
        // Cannot generate admin link from front
        if (!defined('_PS_ADMIN_DIR_')) {
            return '';
        }

        if ($this->shopContext->isShop17()) {
            $uri = $this->link->getAdminLink($controller, $withToken, $sfRouteParams, $params);
        } else {
            $uri = $this->getAdminLink16($controller, $withToken, $params);
        }

        if (!$withToken) {
            // FIXME: getAdminLink still adds the token (sometimes)
            $uri = preg_replace('/&_token=[^&]*/', '', $uri);
        }

        if ($shopId) {
            $uri = $this->fixVirtualUri($uri, $shopId);
        }

        return $uri;
    }

    /**
     * @param int|null $shopId
     * @param bool|null $ssl
     * @param bool $relativeProtocol
     *
     * @return string
     */
    public function getAdminBaseLink($shopId = null, $ssl = null, $relativeProtocol = false)
    {
        /* @phpstan-ignore-next-line */
        if (method_exists($this->link, 'getAdminBaseLink')) {
            return $this->link->getAdminBaseLink($shopId, $ssl, $relativeProtocol);
        } else {
            return $this->getAdminBaseLink16($shopId, $ssl, $relativeProtocol);
        }
    }

    /**
     * @param string $controller
     * @param bool $withToken
     * @param array $params
     * @param int $shopId
     *
     * @return string
     */
    public function getAdminLink16($controller, $withToken, array $params, $shopId = null)
    {
        $paramsAsString = '';
        foreach ($params as $key => $value) {
            $paramsAsString .= "&$key=$value";
        }

        return $this->getAdminBaseLink16($shopId) .
            basename(_PS_ADMIN_DIR_) . '/' . // admin path
            $this->link->getAdminLink($controller, $withToken) .
            $paramsAsString;
    }

    /**
     * @param int|null $shopId
     * @param bool|null $ssl
     * @param bool $relativeProtocol
     *
     * @return string
     */
    public function getAdminBaseLink16($shopId = null, $ssl = null, $relativeProtocol = false)
    {
        $path = __PS_BASE_URI__; // physical + virtual
        if ($shopId) {
            $shop = new \Shop($shopId);
            $path = $shop->physical_uri . $shop->virtual_uri;
        }

        return \Tools::getShopDomainSsl(true) . $path;
    }

    /**
     * @return \Link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param bool $withToken
     *
     * @return string
     */
    public function getDashboardLink($withToken = false)
    {
        return $this->getAdminLink('AdminDashboard', false);
    }

    /**
     * @param int $shopId
     * @param bool $withToken
     * @param string $moduleName
     *
     * @return string
     */
    public function getModuleContentsLink($shopId, $withToken = false, $moduleName = 'ps_accounts')
    {
        return $this->getAdminLink(
            'AdminModules',
            $withToken,
            [],
            [
                'configure' => $moduleName,
                'setShopContext' => 's-' . $shopId,
            ]
        );
    }

    /**
     * @param string $link
     *
     * @return string
     */
    public function cleanSlashes($link)
    {
        $link = preg_replace('@^(http|https)://@', '\1:SCHEME_SLASHES', $link);
        $link = preg_replace('/\/+/', '/', $link);

        return preg_replace('@^(http|https):SCHEME_SLASHES@', '\1://', $link);
    }

    /**
     * @param string $link
     *
     * @return string
     */
    public function getTrailingSlash($link)
    {
        return preg_match('/\/(\?|$)/', $link) ? '/' : '';
    }

    /**
     * @param string $uri
     * @param int $shopId
     *
     * @return string
     */
    private function fixVirtualUri($uri, $shopId)
    {
        $shop = new \Shop($shopId);

        return preg_replace(
            '@^(https://[^/]+)' . preg_quote($shop->physical_uri, '@') . '.*' . preg_quote(basename(_PS_ADMIN_DIR_), '@') . '@',
            //'$1' . $shop->physical_uri . basename(_PS_ADMIN_DIR_),
            'https://' . $shop->domain_ssl . $shop->physical_uri . basename(_PS_ADMIN_DIR_),
            $uri
        );
    }
}

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
     * @param bool $absolute require an absolute uri
     *
     * @return string
     */
    public function getAdminLink($controller, $withToken = true, $sfRouteParams = [], $params = [], $absolute = false)
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

        return $uri;
    }

    /**
     * @param string $controller
     * @param bool $withToken
     * @param array $params
     *
     * @return string
     */
    public function getAdminLink16($controller, $withToken, array $params)
    {
        $paramsAsString = '';
        foreach ($params as $key => $value) {
            $paramsAsString .= "&$key=$value";
        }

        return \Tools::getShopDomainSsl(true) . // scheme + domain
            __PS_BASE_URI__ . // physical + virtual
            basename(_PS_ADMIN_DIR_) . '/' . // admin path
            $this->link->getAdminLink($controller, $withToken) .
            $paramsAsString;
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
     * Adapter to get adminLink with custom domain
     *
     * @param string $sslDomain shop ssl domain
     * @param string $domain shop domain
     * @param string $controller controller name
     * @param bool $withToken include or not the token in the url
     * @param array $sfRouteParams
     * @param array $params
     *
     * @return string
     *
     * @deprecated in favor of fixAdminLink
     */
    public function getAdminLinkWithCustomDomain($sslDomain, $domain, $controller, $withToken = true, $sfRouteParams = [], $params = [])
    {
        $boBaseUrl = $this->getAdminLink($controller, $withToken, $sfRouteParams, $params);
        $parsedUrl = parse_url($boBaseUrl);

        if ($parsedUrl && isset($parsedUrl['host']) && isset($parsedUrl['scheme'])) {
            return str_replace(
                $parsedUrl['host'],
                $parsedUrl['scheme'] === 'http' ? $domain : $sslDomain,
                $boBaseUrl
            );
        }

        return $boBaseUrl;
    }

    /**
     * @param string $link
     * @param \Shop $shop
     *
     * @return string
     */
    public function fixAdminLink($link, \Shop $shop)
    {
        $parsedUrl = parse_url($link);

        // fix: domain
        if ($parsedUrl && isset($parsedUrl['host']) && isset($parsedUrl['scheme'])) {
            $link = str_replace(
                $parsedUrl['host'],
                $parsedUrl['scheme'] === 'http' ? $shop->domain : $shop->domain_ssl,
                $link
            );
        }

        // fix: physical_uri + virtual_uri
        if ($parsedUrl && isset($parsedUrl['path'])) {
            $script = $this->getScript($link);
            $path = $this->cleanSlashes(
                '/' . $shop->physical_uri . '/' .
                (defined('_PS_ADMIN_DIR_') ? _PS_ADMIN_DIR_ : '') .
                ($script ? '/' . $script : '') .
                $this->getTrailingSlash($link)
            );
            $link = str_replace($parsedUrl['path'], $path, $link);
        }

        return $link;
    }

    /**
     * @param string $link
     *
     * @return string
     */
    public function getScript($link)
    {
        if (preg_match('/^.*?([\w\-_]+\.php).*$/', $link, $matches)) {
            return $matches[1];
        }

        return '';
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
     * @param string $url
     * @param bool $absolute
     *
     * @return string
     *
     * @deprecated broken in early PrestaShop v9 beta
     */
    protected function rel2abs($url, $absolute)
    {
        if ($absolute && !preg_match('/https?:\/\//', $url)) {
            $url = \Tools::getShopDomainSsl(true) . $url;
        }

        return $url;
    }
}

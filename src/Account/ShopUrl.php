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

namespace PrestaShop\Module\PsAccounts\Account;

use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;

class ShopUrl
{
    /**
     * @var string
     */
    private $backOfficeUrl;

    /**
     * @var string
     */
    private $frontendUrl;

    /**
     * @var int
     */
    private $multiShopId;

    /**
     * ShopUrl constructor.
     *
     * @param string $backOfficeUrl
     * @param string $frontendUrl
     * @param int $multiShopId
     */
    public function __construct($backOfficeUrl, $frontendUrl, $multiShopId)
    {
        $this->backOfficeUrl = $backOfficeUrl;
        $this->frontendUrl = $frontendUrl;
        $this->multiShopId = $multiShopId;
    }

    /**
     * @param array $shop
     *
     * @return ShopUrl
     */
    public static function createFromShopData($shop)
    {
        $backOfficeUrl = explode('/index.php', $shop['url'])[0];
        $frontendUrl = rtrim($shop['frontUrl'], '/');
        $multiShopId = (int) $shop['id'];

        return new ShopUrl($backOfficeUrl, $frontendUrl, $multiShopId);
    }

    /**
     * @param ShopUrl $shopUrl
     *
     * @return bool
     */
    public function equals(ShopUrl $shopUrl, $checkBackOfficeUrl = true)
    {
        return ($checkBackOfficeUrl ? $this->backOfficeUrl === $shopUrl->backOfficeUrl : true)
            && $this->frontendUrl === $shopUrl->frontendUrl
            && $this->multiShopId === $shopUrl->multiShopId;
    }

    /**
     * @return string
     */
    public function getBackOfficeUrl()
    {
        return $this->backOfficeUrl;
    }

    /**
     * @return string
     */
    public function getFrontendUrl()
    {
        return $this->frontendUrl;
    }

    /**
     * @return int
     */
    public function getMultiShopId()
    {
        return $this->multiShopId;
    }

    /**
     * Check if the URL has changed compared to the remote status
     *
     * Note: If $checkBackOfficeUrl is false/null, only frontend URL is compared, backOfficeUrl is not considered
     *
     * @param ShopStatus $status
     * @param ShopUrl $localShopUrl
     * @param bool $checkBackOfficeUrl
     *
     * @return bool
     */
    public static function urlChanged(ShopStatus $status, ShopUrl $localShopUrl, $checkBackOfficeUrl = false)
    {
        $cloudShopUrl = new ShopUrl(
            rtrim($status->backOfficeUrl, '/'),
            rtrim($status->frontendUrl, '/'),
            $localShopUrl->getMultiShopId()
        );
        $normalizedLocalShopUrl = new ShopUrl(
            rtrim($localShopUrl->getBackOfficeUrl(), '/'),
            rtrim($localShopUrl->getFrontendUrl(), '/'),
            $localShopUrl->getMultiShopId()
        );

        return !$cloudShopUrl->equals($normalizedLocalShopUrl, $checkBackOfficeUrl);
    }
}

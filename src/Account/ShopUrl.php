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
     * Check if the frontend URL has changed compared to the remote status
     *
     * @param ShopUrl $shopUrl
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function frontendUrlEquals(ShopUrl $shopUrl)
    {
        $cloudFrontendUrl = rtrim($this->frontendUrl, '/');
        $localFrontendUrl = rtrim($shopUrl->getFrontendUrl(), '/');

        if (empty($cloudFrontendUrl) || empty($localFrontendUrl)) {
            throw new \InvalidArgumentException('Frontend URL cannot be empty');
        }

        return $cloudFrontendUrl === $localFrontendUrl;
    }

    /**
     * Check if the backOffice URL has changed compared to the remote status
     * Returns true if backOfficeUrl changed
     *
     * @param ShopUrl $shopUrl
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function backOfficeUrlEquals(ShopUrl $shopUrl)
    {
        $cloudBackOfficeUrl = rtrim($this->getBackOfficeUrl(), '/');
        $localBackOfficeUrl = rtrim($shopUrl->getBackOfficeUrl(), '/');

        if (empty($cloudBackOfficeUrl) || empty($localBackOfficeUrl)) {
            throw new \InvalidArgumentException('BackOffice URL cannot be empty');
        }

        return $cloudBackOfficeUrl === $localBackOfficeUrl;
    }

    /**
     * @return ShopUrl
     */
    public function trimmed()
    {
        return new ShopUrl(
            rtrim($this->getBackOfficeUrl(), '/'),
            rtrim($this->getFrontendUrl(), '/'),
            $this->getMultiShopId()
        );
    }

    /**
     * Create a new ShopUrl from the status
     *
     * @param ShopStatus $status
     * @param int $multiShopId
     *
     * @return ShopUrl
     */
    public static function createFromStatus(ShopStatus $status, $multiShopId)
    {
        return new ShopUrl($status->backOfficeUrl, $status->frontendUrl, $multiShopId);
    }
}

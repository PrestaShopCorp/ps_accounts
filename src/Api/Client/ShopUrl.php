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

namespace PrestaShop\Module\PsAccounts\Api\Client;

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
     * @var string
     */
    private $multiShopId;

    /**
     * ShopUrl constructor.
     *
     * @param string $backOfficeUrl
     * @param string $frontendUrl
     * @param string $multiShopId
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
        $multiShopId = $shop['id'];

        return new ShopUrl($backOfficeUrl, $frontendUrl, $multiShopId);
    }

    /**
     * @param ShopUrl $shopUrl
     *
     * @return void
     */
    public function equals(ShopUrl $shopUrl)
    {
        return $this->backOfficeUrl === $shopUrl->backOfficeUrl
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
}

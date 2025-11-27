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
     * Check if the URL has changed compared to the remote status
     *
     * Note:
     * - If $checkParts is empty, only frontendUrl is compared, backOfficeUrl and multiShopId are not considered
     * - If $checkParts is an array, only the specified part(s) are checked: ['backOfficeUrl'], ['frontendUrl'], ['multiShopId']
     * - Multiple parts can be checked: ['backOfficeUrl', 'frontendUrl']
     * - If $exclusive is true, returns true only if the specified parts changed AND no other parts changed
     *
     * @param ShopStatus $status
     * @param ShopUrl $localShopUrl
     * @param array $checkParts Parts to check: array like ['backOfficeUrl'], ['frontendUrl'], ['multiShopId'] or ['backOfficeUrl', 'frontendUrl']
     * @param bool $exclusive If true, returns true only if the specified parts changed and no other parts changed
     *
     * @return bool
     */
    public static function urlChanged(ShopStatus $status, ShopUrl $localShopUrl, array $checkParts = ['frontendUrl'], $exclusive = false)
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

        // Determine which parts to check based on $checkParts
        $checkBackOfficeUrl = in_array('backOfficeUrl', $checkParts);
        $checkFrontendUrl = in_array('frontendUrl', $checkParts);
        $checkMultiShopId = in_array('multiShopId', $checkParts);

        // Check if any of the specified parts changed
        $hasChanged = false;

        if ($checkBackOfficeUrl && $cloudShopUrl->getBackOfficeUrl() !== $normalizedLocalShopUrl->getBackOfficeUrl()) {
            $hasChanged = true;
        }
        if ($checkFrontendUrl && $cloudShopUrl->getFrontendUrl() !== $normalizedLocalShopUrl->getFrontendUrl()) {
            $hasChanged = true;
        }
        if ($checkMultiShopId && $cloudShopUrl->getMultiShopId() !== $normalizedLocalShopUrl->getMultiShopId()) {
            $hasChanged = true;
        }

        // If exclusive mode, verify that other parts haven't changed
        if ($exclusive && $hasChanged) {
            $allParts = ['backOfficeUrl', 'frontendUrl', 'multiShopId'];
            $otherParts = array_diff($allParts, $checkParts);

            foreach ($otherParts as $part) {
                $partChanged = false;
                if ($part === 'backOfficeUrl' && $cloudShopUrl->getBackOfficeUrl() !== $normalizedLocalShopUrl->getBackOfficeUrl()) {
                    $partChanged = true;
                } elseif ($part === 'frontendUrl' && $cloudShopUrl->getFrontendUrl() !== $normalizedLocalShopUrl->getFrontendUrl()) {
                    $partChanged = true;
                } elseif ($part === 'multiShopId' && $cloudShopUrl->getMultiShopId() !== $normalizedLocalShopUrl->getMultiShopId()) {
                    $partChanged = true;
                }

                if ($partChanged) {
                    return false; // Another part also changed, so not exclusive
                }
            }
        }

        return $hasChanged;
    }
}

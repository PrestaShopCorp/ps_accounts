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

namespace PrestaShop\Module\PsAccounts\Http\Client\Guzzle;

/**
 * Construct the guzzle client depending on PrestaShop version
 */
class GuzzleClientFactory
{
    /**
     * @param array $options
     *
     * @return GuzzleClient
     */
    public function create($options)
    {
        return self::getGuzzleMajorVersionNumber() >= 6
            ? new Guzzle7Client($options)
            : new Guzzle5Client($options);
    }

    /**
     * @return int|null
     */
    public static function getGuzzleMajorVersionNumber()
    {
        // Guzzle 7 and above
        if (defined('\GuzzleHttp\ClientInterface::MAJOR_VERSION')) {
            // @phpstan-ignore-next-line
            return (int) \GuzzleHttp\ClientInterface::MAJOR_VERSION;
        }

        // Before Guzzle 7
        if (defined('\GuzzleHttp\ClientInterface::VERSION')) {
            // @phpstan-ignore-next-line
            return (int) \GuzzleHttp\ClientInterface::VERSION[0];
        }

        return null;
    }
}

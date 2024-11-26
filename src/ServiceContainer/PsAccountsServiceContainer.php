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

namespace PrestaShop\Module\PsAccounts\ServiceContainer;

use Monolog\Logger;
use PrestaShop\Module\PsAccounts\Log\Logger as LoggerFactory;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShopCorp\LightweightContainer\ServiceContainer\ServiceContainer;

class PsAccountsServiceContainer extends ServiceContainer
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string[]
     */
    protected $provides = [
        Provider\ApiClientProvider::class,
        Provider\CommandProvider::class,
        Provider\DefaultProvider::class,
        Provider\OAuth2Provider::class,
        Provider\RepositoryProvider::class,
        Provider\SessionProvider::class,
    ];

    /**
     * @return Logger
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            $this->logger = LoggerFactory::create(
                $this->getParameterWithDefault(
                    'ps_accounts.log_level',
                    LoggerFactory::ERROR
                )
            );
        }

        return $this->logger;
    }
}
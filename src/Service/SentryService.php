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

namespace PrestaShop\Module\PsAccounts\Service;

use Module;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use Ps_accounts;
use Raven_Client;

class SentryService
{
    /**
     * @var Raven_Client
     */
    protected $client;

    /**
     * @var Ps_accounts
     */
    private $module;

    /**
     * ErrorHandler constructor.
     *
     * @param string $sentryCredentials
     * @param string $environment
     * @param Ps_accounts $module
     *
     * @throws \Raven_Exception
     * @throws \Exception
     */
    public function __construct(
        $sentryCredentials,
        $environment,
        Ps_accounts $module
    ) {
        $this->module = $module;

        /** @var OwnerSession $ownerSession */
        $ownerSession = $module->getService(OwnerSession::class);

        /** @var LinkShop $linkShop */
        $linkShop = $this->module->getService(LinkShop::class);

        $this->client = new Raven_Client(
            $sentryCredentials,
            [
                'environment' => $environment,
                'release' => \Ps_accounts::VERSION,
                'tags' => [
                    'php_version' => phpversion(),
                    'ps_accounts_version' => \Ps_accounts::VERSION,
                    'prestashop_version' => _PS_VERSION_,
                    'ps_accounts_is_enabled' => \Module::isEnabled('ps_accounts'),
                    'email' => $linkShop->getOwnerEmail(),
                    'shop_uuid' => $linkShop->getShopUuid(),
                ],
            ]
        );

        $this->client->install();
    }

    /**
     * @param mixed $exception
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function capture($exception)
    {
        /** @var Ps_accounts $psAccounts */
        $psAccounts = Module::getInstanceByName('ps_accounts');

        /** @var self $instance */
        $instance = $psAccounts->getService(self::class);

        $psAccounts->getLogger()->debug($exception);

        $instance->client->captureException($exception);
    }

    /**
     * @param mixed $exception
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function captureAndRethrow($exception)
    {
        self::capture($exception);

        throw $exception;
    }
}

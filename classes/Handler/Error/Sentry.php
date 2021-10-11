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

namespace PrestaShop\Module\PsAccounts\Handler\Error;

use Module;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use Ps_accounts;
use Raven_Client;

/**
 * Handle Error.
 */
class Sentry
{
    /**
     * @var Raven_Client
     */
    protected $client;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * ErrorHandler constructor.
     *
     * @param string $sentryCredentials
     * @param string $environment
     * @param ConfigurationRepository $configuration
     *
     * @throws \Raven_Exception
     */
    public function __construct(
        $sentryCredentials,
        $environment,
        ConfigurationRepository $configuration
    ) {
        $this->configuration = $configuration;

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
                    'email' => $this->configuration->getFirebaseEmail(),
                    'shop_uuid' => $this->configuration->getShopUuid(),
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
     * @throws \Throwable
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
     * @throws \Throwable
     */
    public static function captureAndRethrow($exception)
    {
        self::capture($exception);

        throw $exception;
    }

    /**
     * @return void
     */
    private function __clone()
    {
    }
}

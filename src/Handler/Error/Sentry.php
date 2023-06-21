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

use Context;
use Module;
use PrestaShop\Module\PsAccounts\Handler\ModuleFilteredRavenClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use Ps_accounts;
use Raven_Client;

/**
 * Sentry service
 *
 * FIXME: maj Sentry
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
     * @var float
     */
    private $sampleRateFront = 0.2;

    /**
     * @var float
     */
    private $sampleRateBack = 1;

    /**
     * @var int
     */
    private $errorTypes = E_ALL & ~E_STRICT & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE;

    /**
     * ErrorHandler constructor.
     *
     * @param string $sentryCredentials
     * @param string $environment
     * @param ConfigurationRepository $configuration
     * @param Context $context
     *
     * @throws \Raven_Exception
     */
    public function __construct(
        string $sentryCredentials,
        string $environment,
        ConfigurationRepository $configuration,
        Context $context
    ) {
        $this->configuration = $configuration;

        $this->client = new ModuleFilteredRavenClient(
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
                'error_types' => $this->errorTypes,
                'sample_rate' => $this->isContextInFrontOffice($context) ?
                    $this->sampleRateFront :
                    $this->sampleRateBack,
            ]
        );

        $moduleName = 'ps_accounts';

        // We use realpath to get errors even if module is behind a symbolic link
        $this->client->setAppPath(realpath(_PS_MODULE_DIR_ . $moduleName . '/'));
        // - Do no not add the shop root folder, it will exclude everything even if specified in the app path.
        // - Excluding vendor/ avoids errors comming from one of your libraries library when called by another module.
        $this->client->setExcludedAppPaths([
            realpath(_PS_MODULE_DIR_ . $moduleName . '/vendor/'),
        ]);
        $this->client->setExcludedDomains(['127.0.0.1', 'localhost', '.local']);

        // Other conditions can be done here to prevent the full installation of the client:
        // - PHP versions,
        // - PS versions,
        // - Integration environment,
        // - ...

        if (version_compare((string) phpversion(), '7.4.0', '>=') &&
            version_compare(_PS_VERSION_, '1.7.8.0', '<')) {
            return;
        }

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

    /**
     * @return bool
     */
    private function isContextInFrontOffice(Context $context = null)
    {
        /*
        Some shops have trouble to refresh the cache of the service container.
        To avoid issues on production after an upgrade, context has been made optional.
        ToDo: Remove the nullable later.
        */
        if (!$context) {
            return false;
        }
        /** @var \Controller|null $controller */
        $controller = $context->controller;
        if (!$controller) {
            return false;
        }

        return in_array($controller->controller_type, ['front', 'modulefront']);
    }
}

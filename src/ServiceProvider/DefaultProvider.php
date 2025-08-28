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

namespace PrestaShop\Module\PsAccounts\ServiceProvider;

use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Adapter;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Api\Client\ServicesBillingClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Cqrs\QueryBus;
use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker;
use PrestaShop\Module\PsAccounts\Installer\Installer;
use PrestaShop\Module\PsAccounts\Polyfill\ConfigurationStorageSession;
use PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter;
use PrestaShop\Module\PsAccounts\Provider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\AdminTokenService;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\PsBillingService;
use PrestaShop\Module\PsAccounts\Service\SentryService;
use PrestaShop\Module\PsAccounts\Service\UpgradeService;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShopCorp\LightweightContainer\ServiceContainer\Contract\IServiceProvider;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShopCorp\LightweightContainer\ServiceContainer\ServiceContainer;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DefaultProvider implements IServiceProvider
{
    /**
     * @param ServiceContainer $container
     *
     * @return void
     */
    public function provide(ServiceContainer $container)
    {
        $container->set('ps_accounts.logger', $container->getLogger());

        $container->registerProvider('ps_accounts.context', static function () {
            return \Context::getContext();
        });
        $container->registerProvider('ps_accounts.module', static function () {
            return \Module::getInstanceByName('ps_accounts');
        });
        // Entities ?
        $container->registerProvider(StatusManager::class, static function () use ($container) {
            return new StatusManager(
                $container->get(ShopSession::class),
                $container->get(AccountsService::class),
                $container->get(ConfigurationRepository::class)
            );
        });
        // Adapter
        $container->registerProvider(Adapter\Configuration::class, static function () use ($container) {
            return new Adapter\Configuration(
                $container->get('ps_accounts.context')
            );
        });
        $container->registerProvider(Adapter\Link::class, static function () use ($container) {
            return new Adapter\Link(
                $container->get(ShopContext::class)
            );
        });
        // Services
        $container->registerProvider(AnalyticsService::class, static function () use ($container) {
            return new AnalyticsService(
                $container->getParameter('ps_accounts.segment_write_key'),
                $container->get('ps_accounts.logger')
            );
        });
        $container->registerProvider(PsAccountsService::class, static function () use ($container) {
            return new PsAccountsService(
                $container->get('ps_accounts.module')
            );
        });
        $container->registerProvider(PsBillingService::class, static function () use ($container) {
            return new PsBillingService(
                $container->get(ServicesBillingClient::class),
                $container->get(ShopTokenRepository::class),
                $container->get(ConfigurationRepository::class)
            );
        });
        $container->registerProvider(SentryService::class, static function () use ($container) {
            return new SentryService(
                $container->getParameter('ps_accounts.sentry_credentials'),
                $container->getParameter('ps_accounts.environment'),
                $container->get(StatusManager::class),
                $container->get('ps_accounts.context')
            );
        });
        $container->registerProvider(UpgradeService::class, static function () use ($container) {
            return new UpgradeService(
                $container->get(ConfigurationRepository::class)
            );
        });
        $container->registerProvider(AdminTokenService::class, static function () {
            return new AdminTokenService();
        });
        $container->registerProvider(ProofManager::class, static function () use ($container) {
            return new ProofManager(
                $container->get(ConfigurationRepository::class)
            );
        });
        // "Providers"
        $container->registerProvider(Provider\ShopProvider::class, static function () use ($container) {
            return new Provider\ShopProvider(
                $container->get(ShopContext::class),
                $container->get(Link::class),
                $container->get(StatusManager::class),
                $container->get(OAuth2Service::class)
            );
        });
        // Context
        $container->registerProvider(ShopContext::class, static function () use ($container) {
            return new ShopContext(
                $container->get(ConfigurationRepository::class),
                $container->get('ps_accounts.context')
            );
        });
        // CQRS
        $container->registerProvider(CommandBus::class, static function () use ($container) {
            return new CommandBus(
                $container->get('ps_accounts.module')
            );
        });
        $container->registerProvider(QueryBus::class, static function () use ($container) {
            return new QueryBus(
                $container->get('ps_accounts.module')
            );
        });
        // Factories
        $container->registerProvider(CircuitBreaker\Factory::class, static function () use ($container) {
            return new CircuitBreaker\Factory(
                $container->get(Adapter\Configuration::class)
            );
        });
        // Installer
        $container->registerProvider(Installer::class, static function () use ($container) {
            return new Installer(
                $container->get(ShopContext::class),
                $container->get(Link::class)
            );
        });
        // Presenter
        $container->registerProvider(PsAccountsPresenter::class, static function () use ($container) {
            return new PsAccountsPresenter(
                $container->get('ps_accounts.module')
            );
        });
        // PHP Session
        $container->registerProvider(
            '\Symfony\Component\HttpFoundation\Session\SessionInterface',
            static function () use ($container) {
                $module = $container->get('ps_accounts.module');

                $core = $module->getCoreServiceContainer();
                if ($core) {
                    try {
                        /**
                         * @var SessionInterface $session
                         * @phpstan-ignore-next-line
                         */
                        $session = $core->get('session');
                        /* @phpstan-ignore-next-line */
                    } catch (ServiceNotFoundException $e) {
                        try {
                            // FIXME: fix for 1.7.7.x
                            global $kernel;
                            $session = $kernel->getContainer()->get('session');
                            /* @phpstan-ignore-next-line */
                        } catch (ServiceNotFoundException $e) {
                            // FIXME: fix for 9.x
                            global $request;
                            $session = $request->getSession();
                        }
                    }

                    return $session;
                }

                return $container->get(ConfigurationStorageSession::class);
            }
        );
        $container->registerProvider(
            ConfigurationStorageSession::class,
            static function () use ($container) {
                // Fallback session object
                // FIXME: create an interface for it
                $session = new ConfigurationStorageSession(
                    $container->get(Adapter\Configuration::class)
                );
                $session->start();

                return $session;
            }
        );
    }
}

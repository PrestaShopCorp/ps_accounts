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

namespace PrestaShop\Module\PsAccounts\ServiceContainer\Provider;

use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Adapter;
use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Api\Client\ServicesBillingClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Factory\CircuitBreakerFactory;
use PrestaShop\Module\PsAccounts\Installer\Installer;
use PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter;
use PrestaShop\Module\PsAccounts\Provider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\PsBillingService;
use PrestaShop\Module\PsAccounts\Service\SentryService;
use PrestaShop\Module\PsAccounts\ServiceContainer\Contract\IServiceProvider;
use PrestaShop\Module\PsAccounts\ServiceContainer\ServiceContainer;

class DefaultProvider implements IServiceProvider
{
    /**
     * @param ServiceContainer $container
     *
     * @return void
     */
    public function provide(ServiceContainer $container)
    {
        $container->registerProvider('ps_accounts.context', function () {
            return \Context::getContext();
        });
        $container->registerProvider('ps_accounts.logger', function () {
            return \PrestaShop\Module\PsAccounts\Log\Logger::create();
        });
        $container->registerProvider('ps_accounts.module', function () {
            return \Module::getInstanceByName('ps_accounts');
        });
        // Entities ?
        $container->registerProvider(LinkShop::class, function () use ($container) {
            return new LinkShop(
                $container->get(ConfigurationRepository::class)
            );
        });
        // Adapter
        $container->registerProvider(Adapter\Configuration::class, function () use ($container) {
            return new Adapter\Configuration(
                $container->get('ps_accounts.context')
            );
        });
        $container->registerProvider(Adapter\Link::class, function () use ($container) {
            return new Adapter\Link(
                $container->get(ShopContext::class)
            );
        });
        // Services
        $container->registerProvider(AnalyticsService::class, function () use ($container) {
            return new AnalyticsService(
                $container->getParameter('ps_accounts.segment_write_key'),
                $container->get('ps_accounts.logger')
            );
        });
        $container->registerProvider(PsAccountsService::class, function () use ($container) {
            return new PsAccountsService(
                $container->get('ps_accounts.module')
            );
        });
        $container->registerProvider(PsBillingService::class, function () use ($container) {
            return new PsBillingService(
                $container->get(ServicesBillingClient::class),
                $container->get(ShopTokenRepository::class),
                $container->get(ConfigurationRepository::class)
            );
        });
        $container->registerProvider(SentryService::class, function () use ($container) {
            return new SentryService(
                $container->getParameter('ps_accounts.sentry_credentials'),
                $container->getParameter('ps_accounts.environment'),
                $container->get(LinkShop::class),
                $container->get('ps_accounts.context')
            );
        });
        // "Providers"
        $container->registerProvider(Provider\RsaKeysProvider::class, function () use ($container) {
            return new Provider\RsaKeysProvider(
                $container->get(ConfigurationRepository::class)
            );
        });
        $container->registerProvider(Provider\ShopProvider::class, function () use ($container) {
            return new Provider\ShopProvider(
                $container->get(ShopContext::class),
                $container->get(Link::class)
            );
        });
        // Context
        $container->registerProvider(ShopContext::class, function () use ($container) {
            return new ShopContext(
                $container->get(ConfigurationRepository::class),
                $container->get('ps_accounts.context')
            );
        });
        // CQRS
        $container->registerProvider(CommandBus::class, function () use ($container) {
            return new CommandBus(
                $container->get('ps_accounts.module')
            );
        });
        // Factories
        $container->registerProvider(CircuitBreakerFactory::class, function () use ($container) {
            return new CircuitBreakerFactory(
                $container->get(Configuration::class)
            );
        });
        // Installer
        $container->registerProvider(Installer::class, function () use ($container) {
            return new Installer(
                $container->get(ShopContext::class),
                $container->get(Link::class)
            );
        });
        // Presenter
        $container->registerProvider(PsAccountsPresenter::class, function () use ($container) {
            return new PsAccountsPresenter(
                $container->get('ps_accounts.module')
            );
        });
    }
}

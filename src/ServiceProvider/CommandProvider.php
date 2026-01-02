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

use PrestaShop\Module\PsAccounts\Account\CommandHandler\CleanupIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\CreateIdentitiesHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\CreateIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\IdentifyContactHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\MigrateOrCreateIdentitiesV8Handler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\MigrateOrCreateIdentityV8Handler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\RestoreIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpdateBackOfficeUrlHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpdateBackOfficeUrlsHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\VerifyIdentitiesHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\VerifyIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\Session;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\UpgradeService;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShopCorp\LightweightContainer\ServiceContainer\Contract\IServiceProvider;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShopCorp\LightweightContainer\ServiceContainer\ServiceContainer;

class CommandProvider implements IServiceProvider
{
    public function provide(ServiceContainer $container)
    {
        $container->registerProvider(CreateIdentityHandler::class, static function () use ($container) {
            return new CreateIdentityHandler(
                $container->get(AccountsService::class),
                $container->get(ShopProvider::class),
                $container->get(OAuth2Client::class),
                $container->get(StatusManager::class),
                $container->get(CommandBus::class)
            );
        });
        $container->registerProvider(CreateIdentitiesHandler::class, static function () use ($container) {
            return new CreateIdentitiesHandler(
                $container->get(ShopContext::class),
                $container->get(CommandBus::class)
            );
        });
        $container->registerProvider(VerifyIdentityHandler::class, static function () use ($container) {
            return new VerifyIdentityHandler(
                $container->get(AccountsService::class),
                $container->get(ShopProvider::class),
                $container->get(StatusManager::class),
                $container->get(Session\ShopSession::class),
                $container->get(ProofManager::class)
            );
        });
        $container->registerProvider(VerifyIdentitiesHandler::class, static function () use ($container) {
            return new VerifyIdentitiesHandler(
                $container->get(ShopContext::class),
                $container->get(CommandBus::class)
            );
        });
        $container->registerProvider(IdentifyContactHandler::class, static function () use ($container) {
            return new IdentifyContactHandler(
                $container->get(AccountsService::class),
                $container->get(StatusManager::class),
                $container->get(Session\ShopSession::class),
                $container->get(Session\Firebase\OwnerSession::class)
            );
        });
        $container->registerProvider(MigrateOrCreateIdentitiesV8Handler::class, static function () use ($container) {
            return new MigrateOrCreateIdentitiesV8Handler(
                $container->get(ShopContext::class),
                $container->get(CommandBus::class)
            );
        });
        $container->registerProvider(MigrateOrCreateIdentityV8Handler::class, static function () use ($container) {
            return new MigrateOrCreateIdentityV8Handler(
                $container->get(AccountsService::class),
                $container->get(OAuth2Service::class),
                $container->get(ShopProvider::class),
                $container->get(StatusManager::class),
                $container->get(ProofManager::class),
                $container->get(ConfigurationRepository::class),
                $container->get(CommandBus::class),
                $container->get(UpgradeService::class)
            );
        });
        $container->registerProvider(RestoreIdentityHandler::class, static function () use ($container) {
            return new RestoreIdentityHandler(
                $container->get(OAuth2Client::class),
                $container->get(StatusManager::class),
                $container->get(UpgradeService::class),
                $container->get(CommandBus::class)
            );
        });
        $container->registerProvider(CleanupIdentityHandler::class, static function () {
            return new CleanupIdentityHandler();
        });
        $container->registerProvider(UpdateBackOfficeUrlHandler::class, static function () use ($container) {
            return new UpdateBackOfficeUrlHandler(
                $container->get(AccountsService::class),
                $container->get(StatusManager::class),
                $container->get(ShopProvider::class),
                $container->get(Session\ShopSession::class),
                $container->get(ConfigurationRepository::class)
            );
        });
        $container->registerProvider(UpdateBackOfficeUrlsHandler::class, static function () use ($container) {
            return new UpdateBackOfficeUrlsHandler(
                $container->get(ShopContext::class),
                $container->get(CommandBus::class)
            );
        });
    }
}

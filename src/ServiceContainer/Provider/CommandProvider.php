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

use PrestaShop\Module\PsAccounts\Account\CommandHandler\CreateIdentitiesHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\CreateIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\DeleteUserShopHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpdateUserShopHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpgradeModuleHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpgradeModulesHandler;
use PrestaShop\Module\PsAccounts\Account\Session;
use PrestaShop\Module\PsAccounts\Account\ShopIdentity;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShopCorp\LightweightContainer\ServiceContainer\Contract\IServiceProvider;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShopCorp\LightweightContainer\ServiceContainer\ServiceContainer;

class CommandProvider implements IServiceProvider
{
    public function provide(ServiceContainer $container)
    {
        $container->registerProvider(DeleteUserShopHandler::class, static function () use ($container) {
            return new DeleteUserShopHandler(
                $container->get(AccountsClient::class),
                $container->get(ShopContext::class),
                $container->get(Session\Firebase\ShopSession::class),
                $container->get(Session\Firebase\OwnerSession::class)
            );
        });
        $container->registerProvider(UpdateUserShopHandler::class, static function () use ($container) {
            return new UpdateUserShopHandler(
                $container->get(AccountsClient::class),
                $container->get(ShopContext::class),
                $container->get(Session\Firebase\ShopSession::class),
                $container->get(Session\Firebase\OwnerSession::class)
            );
        });
        $container->registerProvider(UpgradeModuleHandler::class, static function () use ($container) {
            return new UpgradeModuleHandler(
                $container->get(AccountsClient::class),
                $container->get(ShopIdentity::class),
                $container->get(Session\ShopSession::class)
            );
        });
        $container->registerProvider(UpgradeModulesHandler::class, static function () use ($container) {
            return new UpgradeModulesHandler(
                $container->get(CommandBus::class),
                $container->get(ConfigurationRepository::class)
            );
        });
        $container->registerProvider(CreateIdentityHandler::class, static function () use ($container) {
            return new CreateIdentityHandler(
                $container->get(AccountsClient::class),
                $container->get(ShopProvider::class),
                $container->get(Oauth2Client::class),
                $container->get(ShopIdentity::class)
            );
        });
        $container->registerProvider(CreateIdentitiesHandler::class, static function () use ($container) {
            return new CreateIdentitiesHandler(
                $container->get(ShopContext::class),
                $container->get(CommandBus::class)
            );
        });
    }
}

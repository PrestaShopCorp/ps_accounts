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

use PrestaShop\Module\PsAccounts\Account\CommandHandler\DeleteUserShopHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\LinkShopHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UnlinkShopHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpdateUserShopHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpgradeModuleHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpgradeModuleMultiHandler;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
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
                $container->get(ShopSession::class),
                $container->get(OwnerSession::class)
            );
        });
        $container->registerProvider(LinkShopHandler::class, static function () use ($container) {
            return new LinkShopHandler(
                $container->get(LinkShop::class)
            );
        });
        $container->registerProvider(UnlinkShopHandler::class, static function () use ($container) {
            return new UnlinkShopHandler(
                $container->get(LinkShop::class),
                $container->get(AnalyticsService::class),
                $container->get(ShopProvider::class)
            );
        });
        $container->registerProvider(UpdateUserShopHandler::class, static function () use ($container) {
            return new UpdateUserShopHandler(
                $container->get(AccountsClient::class),
                $container->get(ShopContext::class),
                $container->get(ShopSession::class),
                $container->get(OwnerSession::class)
            );
        });
        $container->registerProvider(UpgradeModuleHandler::class, static function () use ($container) {
            return new UpgradeModuleHandler(
                $container->get(AccountsClient::class),
                $container->get(LinkShop::class),
                $container->get(ShopSession::class),
                $container->get(ShopContext::class),
                $container->get(ConfigurationRepository::class),
                $container->get(CommandBus::class)
            );
        });
        $container->registerProvider(UpgradeModuleMultiHandler::class, static function () use ($container) {
            return new UpgradeModuleMultiHandler(
                $container->get(CommandBus::class),
                $container->get(ConfigurationRepository::class)
            );
        });
    }
}

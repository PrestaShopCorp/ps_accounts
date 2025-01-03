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

use PrestaShop\Module\PsAccounts\Factory\PrestaShopSessionFactory;
use PrestaShop\Module\PsAccounts\Middleware\Oauth2Middleware;
use PrestaShop\Module\PsAccounts\Provider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShopCorp\LightweightContainer\ServiceContainer\Contract\IServiceProvider;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShopCorp\LightweightContainer\ServiceContainer\ServiceContainer;

class OAuth2Provider implements IServiceProvider
{
    /**
     * @param ServiceContainer $container
     *
     * @return void
     */
    public function provide(ServiceContainer $container)
    {
        // OAuth2
        $container->registerProvider(Provider\OAuth2\Oauth2Client::class, static function () use ($container) {
            return new Provider\OAuth2\Oauth2Client(
                $container->get(ConfigurationRepository::class)
            );
        });
        $container->registerProvider(Provider\OAuth2\PrestaShopSession::class, static function () {
            return PrestaShopSessionFactory::create();
        });
        $container->registerProvider(Provider\OAuth2\ShopProvider::class, static function () {
            return Provider\OAuth2\ShopProvider::create();
        });
        // Middleware
        $container->registerProvider(Oauth2Middleware::class, static function () use ($container) {
            return new Oauth2Middleware(
                $container->get('ps_accounts.module')
            );
        });
    }
}

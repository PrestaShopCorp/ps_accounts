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

use PrestaShop\Module\PsAccounts\AccountLogin\Middleware\Oauth2Middleware;
use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2Session;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\OAuth2\ApiClient;
use PrestaShop\Module\PsAccounts\OAuth2\Client;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopSession;
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
        $container->registerProvider(ApiClient::class, static function () use ($container) {
            return new ApiClient(
                [
                    ClientConfig::baseUri => $container->getParameter('ps_accounts.oauth2_url'),
                    ClientConfig::sslCheck => $container->getParameter('ps_accounts.check_api_ssl_cert'),
                ],
                $container->get(Client::class),
                $container->get(Link::class),
                _PS_CACHE_DIR_ . DIRECTORY_SEPARATOR . 'ps_accounts'
            );
        });
        $container->registerProvider(Client::class, static function () use ($container) {
            return new Client(
                $container->get(ConfigurationRepository::class)
            );
        });
        $container->registerProvider(OAuth2Session::class, static function () use ($container) {
            return new OAuth2Session(
                $container->get('ps_accounts.module')->getSession(),
                $container->getService(ApiClient::class),
                $container->getService(Client::class)
            );
        });
        $container->registerProvider(PrestaShopSession::class, static function () use ($container) {
            return $container->getService(OAuth2Session::class);
        });
        // Middleware
        $container->registerProvider(Oauth2Middleware::class, static function () use ($container) {
            return new Oauth2Middleware(
                $container->get('ps_accounts.module')
            );
        });
    }
}

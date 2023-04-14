<?php

namespace PrestaShop\Module\PsAccounts\Factory;

use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopClientProvider;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopSession;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PrestaShopSessionFactory
{
    public static function create(): PrestaShopSession
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var SessionInterface $session */
        $session = $module->getContainer()->get('session');

        /** @var PrestaShop $provider */
        $provider = $module->getService(PrestaShopClientProvider::class);

        return new PrestaShopSession($session, $provider);
    }
}

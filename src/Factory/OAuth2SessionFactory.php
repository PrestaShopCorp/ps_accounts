<?php

namespace PrestaShop\Module\PsAccounts\Factory;

use PrestaShop\Module\PsAccounts\Provider\OAuth2\OAuth2Session;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OAuth2SessionFactory
{
    public static function create(): OAuth2Session
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var SessionInterface $session */
        $session = $module->getContainer()->get('session');

        /** @var PrestaShop $provider */
        $provider = $module->getService(PrestaShop::class);

        return new OAuth2Session($session, $provider);
    }
}

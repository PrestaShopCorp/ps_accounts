<?php

namespace PrestaShop\Module\PsAccounts\Factory;

use PrestaShop\Module\PsAccounts\Domain\Account\Entity\AccountSession;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopClientProvider;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AccountSessionFactory
{
    public static function create(): AccountSession
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var SessionInterface $session */
        $session = $module->getContainer()->get('session');

        /** @var PrestaShopClientProvider $provider */
        $provider = $module->getService(PrestaShopClientProvider::class);

        return new AccountSession($session, $provider);
    }
}

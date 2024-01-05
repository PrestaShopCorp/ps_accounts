<?php

namespace PrestaShop\Module\PsAccounts\Factory;

use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopClientProvider;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopSession;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PrestaShopSessionFactory
{
    /**
     * @return PrestaShopSession
     *
     * @throws \PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException
     */
    public static function create()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var SessionInterface $session */
        $session = $module->getSession();

        /** @var PrestaShopClientProvider $provider */
        $provider = $module->getService(PrestaShopClientProvider::class);

        return new PrestaShopSession($session, $provider);
    }
}

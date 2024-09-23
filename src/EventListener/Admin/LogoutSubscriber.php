<?php

namespace PrestaShop\Module\PsAccounts\EventListener\Admin;

use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Middleware\Oauth2Middleware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var Oauth2Middleware
     */
    private $oauth2Middleware;

    public function __construct()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        $this->oauth2Middleware = $module->getService(Oauth2Middleware::class);
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    /**
     * @param LogoutEvent $event
     *
     * @return void
     *
     * @throws \Exception
     */
    public function onLogout(LogoutEvent $event)
    {
        $this->oauth2Middleware->executeLogout();
    }
}

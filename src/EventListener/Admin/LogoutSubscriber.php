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

namespace PrestaShop\Module\PsAccounts\EventListener\Admin;

use PrestaShop\Module\PsAccounts\Middleware\Oauth2Middleware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        if (defined('_PS_VERSION_')
            && version_compare(_PS_VERSION_, '9.0', '>=')) {
            return [
                LogoutEvent::class => 'onLogout',
            ];
        }

        return [];
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
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var Oauth2Middleware $oauth2Middleware */
        $oauth2Middleware = $module->getService(Oauth2Middleware::class);
        $oauth2Middleware->executeLogout();
    }
}

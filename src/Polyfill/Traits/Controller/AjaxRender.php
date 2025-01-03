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

namespace PrestaShop\Module\PsAccounts\Polyfill\Traits\Controller;

use PrestaShopException;

trait AjaxRender
{
    /**
     * @param string|null $value
     * @param string|null $controller
     * @param string|null $method
     *
     * @return void
     *
     * @throws PrestaShopException
     *
     * FIXME: this method might exit OR not
     * TODO: rename to 'ajaxDie' & exit every time
     */
    protected function ajaxRender($value = null, $controller = null, $method = null)
    {
        $controllerBaseClass = \ControllerCore::class;
        if (is_a($this, $controllerBaseClass)) {
            /* @phpstan-ignore-next-line */
            if (method_exists($controllerBaseClass, 'ajaxRender')) {
                /* @phpstan-ignore-next-line */
                parent::ajaxRender($value, $controller, $method);
            //exit;
            } else {
                parent::ajaxDie($value, $controller, $method);
            }
        }
    }
}

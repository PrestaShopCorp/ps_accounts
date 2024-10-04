<?php

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
     */
    protected function ajaxRender($value = null, $controller = null, $method = null)
    {
        $controllerBaseClass = \ControllerCore::class;
        if (is_a($this, $controllerBaseClass)) {
            if (method_exists($controllerBaseClass, 'ajaxRender')) {
                /* @phpstan-ignore-next-line */
                parent::ajaxRender($value, $controller, $method);
            } else {
                parent::ajaxDie($value, $controller, $method);
            }
        }
    }
}

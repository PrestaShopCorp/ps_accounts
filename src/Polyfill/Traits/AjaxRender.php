<?php

namespace PrestaShop\Module\PsAccounts\Polyfill\Traits;

use PrestaShopException;

trait AjaxRender
{
    /**
     * @param null|string $value
     * @param null|string $controller
     * @param null|string $method
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function ajaxRender($value = null, $controller = null, $method = null)
    {
        if (is_a($this, \ControllerCore::class)) {
            if (method_exists((string) get_parent_class($this), 'ajaxRender')) {
                /* @phpstan-ignore-next-line */
                parent::ajaxRender($value, $controller, $method);
            } else {
                parent::ajaxDie($value, $controller, $method);
            }
        }
    }
}

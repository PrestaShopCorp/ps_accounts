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

namespace PrestaShop\Module\PsAccounts\Hook;

trait HookableTrait
{
    /**
     * @param string $methodName
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function executeHook($methodName, array $params = [])
    {
        $hookNamespace = __NAMESPACE__;

        if (strpos($methodName, 'hook') === 0) {
            $class = $hookNamespace . '\\' . ucfirst(preg_replace('/^hook/', '', $methodName));
            $method = 'execute';

            if (is_a($class, Hook::class, true)) {
                $this->getLogger()->debug("execute hook : [{$class}]");
                $hook = (new $class($this));

                return $hook->$method($params);
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function __call($name, array $arguments)
    {
        return $this->executeHook($name, $arguments[0]);
    }
}

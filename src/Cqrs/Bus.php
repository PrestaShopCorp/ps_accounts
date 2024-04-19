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

namespace PrestaShop\Module\PsAccounts\Cqrs;

abstract class Bus
{
    /**
     * @var \Ps_accounts
     */
    protected $module;

    /**
     * @param \Ps_accounts $module
     */
    public function __construct(\Ps_accounts $module)
    {
        $this->module = $module;
    }

    /**
     * @param string $className
     *
     * @return string
     */
    abstract public function resolveHandlerClass($className);

    /**
     * @param mixed $command
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function handle($command)
    {
        $this->module->getLogger()->debug('resolving handler : ' . get_class($command));

        $handler = $this->module->getService($this->resolveHandlerClass(get_class($command)));

        if ($handler && method_exists($handler, 'handle')) {
            /* @phpstan-ignore-next-line */
            $this->module->getLogger()->debug('handling : ' . get_class($handler));
            $this->module->getLogger()->debug('with data : ' . json_encode($command));

            /* @phpstan-ignore-next-line */
            return $handler->handle($command);
        }
        throw new \Exception('handle method not found');
    }
}

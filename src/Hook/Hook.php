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

use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Vendor\Monolog\Logger;
use Ps_accounts;

abstract class Hook
{
    /**
     * @var Ps_accounts
     */
    protected $module;

    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Ps_accounts $module
     *
     * @throws \Exception
     */
    public function __construct(Ps_accounts $module)
    {
        $this->module = $module;
        $this->commandBus = $module->getService(CommandBus::class);
        $this->logger = $module->getLogger();
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    abstract public function execute(array $params = []);

    /**
     * @return string
     */
    public static function getName()
    {
        return lcfirst(preg_replace('/^.*\\\\/', '', static::class));
    }

    /**
     * @return Ps_accounts
     */
    public function getModule()
    {
        return $this->module;
    }
}

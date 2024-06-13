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

<<<<<<<< HEAD:src/Account/Command/LinkShopCommand.php
namespace PrestaShop\Module\PsAccounts\Account\Command;

use PrestaShop\Module\PsAccounts\Account\Dto\LinkShop;

class LinkShopCommand
{
    /**
     * @var LinkShop
     */
    public $payload;

    /**
     * @param LinkShop $payload
     */
    public function __construct(LinkShop $payload)
    {
        $this->payload = $payload;
========
namespace PrestaShop\Module\PsAccounts\Logger;

use Ps_accounts;

class Logger
{
    /**
     * @return \Monolog\Logger
     *
     * @throws \Exception
     */
    public static function getInstance()
    {
        /** @var Ps_accounts $psAccounts */
        $psAccounts = \Module::getInstanceByName('ps_accounts');

        return $psAccounts->getLogger();
>>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):src/Logger/Logger.php
    }
}

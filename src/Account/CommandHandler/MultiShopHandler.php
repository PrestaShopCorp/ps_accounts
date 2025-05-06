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

namespace PrestaShop\Module\PsAccounts\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;

abstract class MultiShopHandler
{
    /**
     * @var ShopContext
     */
    protected $shopContext;

    /**
     * @var CommandBus
     */
    protected $commandBus;

    public function __construct(
        ShopContext $shopContext,
        CommandBus $commandBus
    ) {
        $this->shopContext = $shopContext;
        $this->commandBus = $commandBus;
    }

    /**
     * @param \Closure $handler
     *
     * @return void
     */
    protected function handleMulti($handler)
    {
        foreach ($this->getShopIds() as $multiShopId) {
            $this->shopContext->execInShopContext($multiShopId, function () use ($handler, $multiShopId) {
                $handler($multiShopId);
            });
        }
    }

    /**
     * @return array|null[]
     */
    protected function getShopIds()
    {
        if ($this->shopContext->isMultishopActive()) {
            return $this->shopContext->getMultiShopIds();
        }

        return [null];
    }
}

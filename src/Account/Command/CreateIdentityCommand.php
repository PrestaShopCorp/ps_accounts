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

namespace PrestaShop\Module\PsAccounts\Account\Command;

use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;

class CreateIdentityCommand
{
    /**
     * @var int|null
     */
    public $shopId;

    /**
     * @var bool
     */
    public $renew;

    /**
     * @var string
     */
    public $origin;

    /**
     * @var string|null
     */
    public $source;

    /**
     * @param int|null $shopId
     * @param bool $renew
     * @param string $origin
     * @param string $source
     */
    public function __construct(
        $shopId,
        $renew = false,
        $origin = AccountsService::ORIGIN_INSTALL,
        $source = 'ps_accounts'
    ) {
        $this->shopId = $shopId;
        $this->renew = $renew;
        $this->origin = $origin;
        $this->source = $source;
    }
}

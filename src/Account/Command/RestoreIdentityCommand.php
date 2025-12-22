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

class RestoreIdentityCommand
{
    /**
     * @var int|null
     */
    public $shopId;

    /**
     * @var string
     */
    public $cloudShopId;

    /**
     * @var string
     */
    public $clientId;

    /**
     * @var string
     */
    public $clientSecret;

    /**
     * @var bool
     */
    public $verify;

    /**
     * @var bool
     */
    public $migrate;

    /**
     * @var string|null
     */
    public $migrateFrom;

    /**
     * @var string
     */
    public $origin;

    /**
     * @var string
     */
    public $source;

    /**
     * @param string $cloudShopId
     * @param string $clientId
     * @param string $clientSecret
     * @param bool $verify
     * @param bool $migrate
     * @param string $migrateFrom
     */
    public function __construct(
        $cloudShopId,
        $clientId,
        $clientSecret,
        $verify = false,
        $migrate = false,
        $migrateFrom = null
    ) {
        $this->cloudShopId = $cloudShopId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->verify = $verify;
        $this->migrate = $migrate;
        $this->migrateFrom = $migrateFrom;
        $this->origin = AccountsService::ORIGIN_ADVANCED_SETTINGS;
        $this->source = 'ps_accounts';
    }
}

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

use PrestaShop\Module\PsAccounts\Traits\WithOriginAndSourceTrait;

/**
 * @method $this withVersion(string|null $version)
 * @method string|null getVersion(bool $restoreDefault = true)
 */
class MigrateOrCreateIdentitiesV8Command
{
    use WithOriginAndSourceTrait;

    /**
     * Explicit target version to register once the migration succeeds.
     *
     * Sourced from the upgrade script (always-fresh code) so we do not rely on the
     * possibly-stale \Ps_accounts::VERSION const during a zip upgrade. Null falls back
     * to the const downstream (BC).
     *
     * @var string|null
     */
    public $version;

    public function __construct()
    {
        $this->resetProperties();
    }
}

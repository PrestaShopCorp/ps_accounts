<?php

namespace PrestaShop\Module\PsAccounts\Traits;

use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;

/**
 * @method $this withSource(string $source)
 * @method string getSource(bool $restoreDefault = true)
 * @method $this withOrigin(string $source)
 * @method string getOrigin(bool $restoreDefault = true)
 */
trait WithOriginAndSourceTrait
{
    use WithPropertyTrait;

    /**
     * source module triggering call
     *
     * @var string|null
     */
    public $source;

    /**
     * UX origin triggering call
     *
     * @var string|null
     */
    public $origin;

    /**
     * @return array
     */
    public function getDefaults()
    {
        return [
            'origin' => AccountsService::ORIGIN_INSTALL,
            'source' => 'ps_accounts',
        ];
    }
}

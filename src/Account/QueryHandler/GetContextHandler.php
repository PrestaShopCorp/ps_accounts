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

namespace PrestaShop\Module\PsAccounts\Account\QueryHandler;

use PrestaShop\Module\PsAccounts\Account\Query\GetContextQuery;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\UpgradeService;

class GetContextHandler
{
    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var UpgradeService
     */
    private $upgradeService;

    /**
     * @param ShopProvider $shopProvider
     */
    public function __construct(
        ShopProvider $shopProvider,
        UpgradeService $upgradeService
    ) {
        $this->shopProvider = $shopProvider;
        $this->upgradeService = $upgradeService;
    }

    /**
     * @param GetContextQuery $query
     *
     * @return array
     *
     * @throws AccountsException
     */
    public function handle(GetContextQuery $query)
    {
        return [
            'ps_accounts' => [
                'last_succeeded_upgrade_version' => $this->upgradeService->getVersion(),
                'module_version_from_files' => \Ps_accounts::VERSION,
            ],
            'groups' => $this->shopProvider->getShops(
                $query->source,
                $query->contextType,
                $query->contextId,
                $query->refresh
            ),
        ];
    }
}

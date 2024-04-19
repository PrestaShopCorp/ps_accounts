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

use Exception;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

class ActionAdminLoginControllerLoginAfter extends Hook
{
    /**
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function execute(array $params = [])
    {
        $this->trackLoginEvent($params['employee']);
    }

    /**
     * @param \Employee $employee
     *
     * @return void
     *
     * @throws Exception
     */
    protected function trackLoginEvent(\Employee $employee)
    {
        /** @var AnalyticsService $analyticsService */
        $analyticsService = $this->module->getService(AnalyticsService::class);

        /** @var PsAccountsService $psAccountsService */
        $psAccountsService = $this->module->getService(PsAccountsService::class);

        $account = $psAccountsService->getEmployeeAccount();

        if ($this->module->isShopEdition()) {
            $uid = null;
            if ($account) {
                $uid = $account->getUid();
                $email = $account->getEmail();
            } else {
                $email = $employee->email;
            }
            $analyticsService->identify($uid, null, $email);
            $analyticsService->group($uid, (string) $psAccountsService->getShopUuid());
            $analyticsService->trackUserSignedIntoBackOfficeLocally($uid, $email);
        }
    }
}

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

use AdminLoginPsAccountsController;
use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use Tools;

class ActionAdminLoginControllerSetMedia extends BaseHook
{
    /**
     * @return void
     *
     * @throws IdentityProviderException
     * @throws Exception
     */
    public function execute(array $params = [])
    {
        $this->ps_accounts->getOauth2Middleware()->execute();

        /** @var PsAccountsService $psAccountsService */
        $psAccountsService = $this->ps_accounts->getService(PsAccountsService::class);
        $local = Tools::getValue('mode') === AdminLoginPsAccountsController::PARAM_MODE_LOCAL ||
            !$psAccountsService->getLoginActivated();

        $this->trackLoginPage($local);

        if ($this->ps_accounts->getShopContext()->isShop17() && !$local) {
//            /** @var \PrestaShop\Module\PsAccounts\Adapter\Link $link */
//            $link = $this->getService(\PrestaShop\Module\PsAccounts\Adapter\Link::class);
//            Tools::redirectLink($link->getAdminLink('AdminLoginPsAccounts', false));
            (new AdminLoginPsAccountsController())->run();
            exit;
        }
    }

    /**
     * @param bool $local
     *
     * @return void
     *
     * @throws Exception
     */
    protected function trackLoginPage($local = false)
    {
        if ($this->ps_accounts->isShopEdition()) {
            /** @var PsAccountsService $psAccountsService */
            $psAccountsService = $this->ps_accounts->getService(PsAccountsService::class);
            $account = $psAccountsService->getEmployeeAccount();
            $userId = $account ? $account->getUid() : null;

            /** @var AnalyticsService $analytics */
            $analytics = $this->ps_accounts->getService(AnalyticsService::class);

            if (!$local) {
                $analytics->pageAccountsBoLogin($userId);
            } else {
                $analytics->pageLocalBoLogin($userId);
            }
        }
    }
}

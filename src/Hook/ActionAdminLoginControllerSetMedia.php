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
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Tools;

class ActionAdminLoginControllerSetMedia extends Hook
{
    /**
     * @return void
     *
     * @throws IdentityProviderException
     * @throws Exception
     */
    public function execute(array $params = [])
    {
        $this->module->getOauth2Middleware()->execute();

        /** @var PsAccountsService $psAccountsService */
        $psAccountsService = $this->module->getService(PsAccountsService::class);
        $local = Tools::getValue('mode') === AdminLoginPsAccountsController::PARAM_MODE_LOCAL ||
            !empty(Tools::getValue('reset_token')) ||
            !$psAccountsService->getLoginActivated();

        $this->trackLoginPage($local);

        if (defined('_PS_VERSION_')
            && version_compare(_PS_VERSION_, '8', '>=') && !$local) {
//            /** @var \PrestaShop\Module\PsAccounts\Adapter\Link $link */
//            $link = $this->module->getService(\PrestaShop\Module\PsAccounts\Adapter\Link::class);
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
        if ($this->module->isShopEdition()) {
            /** @var PsAccountsService $psAccountsService */
            $psAccountsService = $this->module->getService(PsAccountsService::class);
            $account = $psAccountsService->getEmployeeAccount();
            $userId = $account ? $account->getUid() : null;

            /** @var AnalyticsService $analytics */
            $analytics = $this->module->getService(AnalyticsService::class);

            if (!$local) {
                $analytics->pageAccountsBoLogin($userId);
            } else {
                $analytics->pageLocalBoLogin($userId);
            }
        }
    }
}

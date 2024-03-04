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
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use Tools;

class ActionAdminControllerInitBefore extends Hook
{
    /**
     * @var PsAccountsService
     */
    private $accountsService;

    /**
     * @var AnalyticsService
     */
    private $analytics;

    public function __construct(\Ps_accounts $module)
    {
        parent::__construct($module);

        $this->accountsService = $this->module->getService(PsAccountsService::class);
        $this->analytics = $this->module->getService(AnalyticsService::class);
    }

    /**
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function execute(array $params = [])
    {
//        $controller = $params['controller'];
//
//        $this->module->getOauth2Middleware()->execute();
//
//        $className = preg_replace('/^.*\\\\/', '', get_class($controller));
////        Logger::getInstance()->error('########################### ' . __CLASS__ . ' ' . $className);
//
//        if ($className === 'AdminLoginController') {
//            $local = Tools::getValue('mode') === AdminLoginPsAccountsController::PARAM_MODE_LOCAL ||
//                !$this->accountsService->getLoginActivated();
//
//            $this->trackLoginPage($local);
//
//            if ($this->module->getShopContext()->isShop17() && !$local) {
////                /** @var \PrestaShop\Module\PsAccounts\Adapter\Link $link */
////                $link = $this->module->getService(\PrestaShop\Module\PsAccounts\Adapter\Link::class);
////                Tools::redirectLink($link->getAdminLink('AdminLoginPsAccounts', false));
//                (new AdminLoginPsAccountsController())->run();
//                exit;
//            }
//        }
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
            $account = $this->accountsService->getEmployeeAccount();
            $userId = $account ? $account->getUid() : null;

            if (!$local) {
                $this->analytics->pageAccountsBoLogin($userId);
            } else {
                $this->analytics->pageLocalBoLogin($userId);
            }
        }
    }
}

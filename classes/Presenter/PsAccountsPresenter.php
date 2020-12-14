<?php
/**
 * 2007-2020 PrestaShop and Contributors.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Presenter;

use Module;
use PrestaShop\Module\PsAccounts\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use Ps_accounts;

/**
 * Construct the psaccounts module.
 */
class PsAccountsPresenter
{
    /**
     * @var PsAccountsService
     */
    protected $psAccountsService;

    /**
     * @var Ps_accounts
     */
    private $module;

    /**
     * @param string $psxName
     *
     * @throws \Exception
     */
    public function __construct($psxName)
    {
        $this->module = Module::getInstanceByName('ps_accounts');

        $this->psAccountsService = $this->module->getService(PsAccountsService::class);
        $this->psAccountsService->setPsxName($psxName);

        // FIXME : don't do this
        $this->psAccountsService->manageOnboarding();
    }

    /**
     * Present the PsAccounts module for vue.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function present()
    {
        try {
            return [
                'psIs17' => $this->psAccountsService->getShopContext()->isShop17(),
                'psAccountsInstallLink' => $this->psAccountsService->getPsAccountsInstallLink(),
                'psAccountsEnableLink' => $this->psAccountsService->getPsAccountsEnableLink(),
                'psAccountsIsInstalled' => Module::isInstalled('ps_accounts'),
                'psAccountsIsEnabled' => Module::isEnabled('ps_accounts'),
                'onboardingLink' => $this->psAccountsService->getLinkAccountUrl(),
                'user' => [
                    'email' => $this->psAccountsService->getEmail(),
                    'emailIsValidated' => $this->psAccountsService->isEmailValidated(),
                    'isSuperAdmin' => $this->psAccountsService->getContext()->employee->isSuperAdmin(),
                ],
                'currentShop' => $this->psAccountsService->getCurrentShop(),
                'isShopContext' => $this->psAccountsService->isShopContext(),
                'shops' => $this->psAccountsService->getShopsTree(),
                'superAdminEmail' => $this->psAccountsService->getSuperAdminEmail(),
                'ssoResendVerificationEmail' => $this->psAccountsService->getSsoAccountUrl(),
                'manageAccountLink' => $this->psAccountsService->getManageAccountLink(),
                'adminAjaxLink' => $this->psAccountsService->getAdminAjaxUrl(),
            ];
        } catch (\Exception $e) {
            $this->module->getService(ErrorHandler::class)
                ->handle($e, $e->getCode());
        }

        return [];
    }
}

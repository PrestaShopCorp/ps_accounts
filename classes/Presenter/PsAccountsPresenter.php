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

namespace PrestaShop\Module\PsAccounts\Presenter;

use Module;
use PrestaShop\Module\PsAccounts\Handler\Error\Sentry;
use PrestaShop\Module\PsAccounts\Installer\Installer;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

/**
 * Construct the psaccounts module.
 */
class PsAccountsPresenter implements PresenterInterface
{
    /**
     * @var ShopProvider
     */
    protected $shopProvider;

    /**
     * @var ShopLinkAccountService
     */
    protected $shopLinkAccountService;

    /**
     * @var ConfigurationRepository
     */
    protected $configuration;

    /**
     * @var Installer
     */
    private $installer;

    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * @var \Ps_accounts
     */
    private $module;

    /**
     * PsAccountsPresenter constructor.
     *
     * @param PsAccountsService $psAccountsService
     * @param ShopProvider $shopProvider
     * @param ShopLinkAccountService $shopLinkAccountService
     * @param Installer $installer
     * @param ConfigurationRepository $configuration
     * @param \Ps_accounts $module
     */
    public function __construct(
        PsAccountsService $psAccountsService,
        ShopProvider $shopProvider,
        ShopLinkAccountService $shopLinkAccountService,
        Installer $installer,
        ConfigurationRepository $configuration,
        \Ps_accounts $module
    ) {
        $this->psAccountsService = $psAccountsService;
        $this->shopProvider = $shopProvider;
        $this->shopLinkAccountService = $shopLinkAccountService;
        $this->installer = $installer;
        $this->configuration = $configuration;
        $this->module = $module;
    }

    /**
     * Present the PsAccounts module data for JS
     *
     * @param string $psxName
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function present($psxName = 'ps_accounts')
    {
        $this->shopLinkAccountService->prepareLinkAccount();

        $shopContext = $this->shopProvider->getShopContext();

        $moduleName = $this->module->name;

        try {
            return array_merge(
                [
                    'psxName' => $psxName,
                    'psIs17' => $shopContext->isShop17(),

                    /////////////////////////////
                    // InstallerPresenter

                    'psAccountsIsInstalled' => true,
                    'psAccountsInstallLink' => null,

                    'psAccountsIsEnabled' => $this->installer->isEnabled($moduleName),
                    'psAccountsEnableLink' => $this->installer->getEnableUrl($moduleName, $psxName),

                    'psAccountsIsUptodate' => true,
                    'psAccountsUpdateLink' => null,

                    ////////////////////////////
                    // PsAccountsPresenter

                    // FIXME :  Mix "SSO user" with "Backend user"
                    'user' => [
                        'uuid' => $this->psAccountsService->getUserUuidV4() ?: null,
                        'email' => $this->psAccountsService->getEmail() ?: null,
                        'emailIsValidated' => $this->psAccountsService->isEmailValidated(),
                        'isSuperAdmin' => $shopContext->getContext()->employee->isSuperAdmin(),
                    ],
                    'backendUser' => [
                        'email' => $shopContext->getContext()->employee->email,
                        'isSuperAdmin' => $shopContext->getContext()->employee->isSuperAdmin(),
                    ],
                    'currentShop' => $this->shopProvider->getCurrentShop($psxName),
                    'isShopContext' => $shopContext->isShopContext(),
                    'superAdminEmail' => $this->psAccountsService->getSuperAdminEmail(),

                    // TODO: link to a page to display an "Update Your PSX" notice
                    'onboardingLink' => $this->module->getParameter('ps_accounts.svc_accounts_ui_url'),
                    'ssoResendVerificationEmail' => $this->module->getParameter('ps_accounts.sso_resend_verification_email_url'),
                    'manageAccountLink' => $this->module->getSsoAccountUrl(),

                    'isOnboardedV4' => $this->psAccountsService->isAccountLinkedV4(),

                    'shops' => $this->shopProvider->getShopsTree($psxName),
                    'employeeId' => $shopContext->getContext()->employee->id,
                    'adminAjaxLink' => $this->psAccountsService->getAdminAjaxUrl(),
                ],
                (new DependenciesPresenter())->present($psxName)
            );
        } catch (\Exception $e) {
            Sentry::captureAndRethrow($e);
        }

        return [];
    }
}

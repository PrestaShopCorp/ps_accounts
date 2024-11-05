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

use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Installer\Installer;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\SentryService;
use PrestaShopException;

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
     * @var LinkShop
     */
    protected $linkShop;

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
     * @param \Ps_accounts $module
     *
     * @throws \Exception
     */
    public function __construct(
        \Ps_accounts $module
    ) {
        $this->module = $module;

        $this->psAccountsService = $module->getService(PsAccountsService::class);
        $this->shopProvider = $module->getService(ShopProvider::class);
        $this->linkShop = $module->getService(LinkShop::class);
        $this->installer = $module->getService(Installer::class);
        $this->configuration = $module->getService(ConfigurationRepository::class);

        $this->configuration->fixMultiShopConfig();
    }

    /**
     * @param string $psxName
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function present($psxName = 'ps_accounts')
    {
        $shopContext = $this->shopProvider->getShopContext();

        $moduleName = (string) $this->module->name;

        $unlinkedShops = $this->shopProvider->getUnlinkedShops(
            $psxName,
            $shopContext->getContext()->employee->id
        );
        $shopBase64 = base64_encode(
            (string) json_encode(array_values($unlinkedShops))
        );
        $onboardingLink = $this->module->getParameter('ps_accounts.accounts_ui_url')
            . '?shops=' . $shopBase64;

        try {
            return array_merge(
                [
                    'currentContext' => [
                        'type' => $shopContext->getShopContext(),
                        'id' => $shopContext->getShopContextId(),
                    ],
                    'psxName' => $psxName,
                    'psIs17' => $shopContext->isShop17(),
                    'psAccountsVersion' => $this->module->version,

                    /////////////////////////////
                    // InstallerPresenter

                    'psAccountsIsInstalled' => true,
                    'psAccountsInstallLink' => null,

                    'psAccountsIsEnabled' => $this->installer->isEnabled((string) $moduleName),
                    'psAccountsEnableLink' => $this->installer->getEnableUrl((string) $moduleName, $psxName),

                    'psAccountsIsUptodate' => true,
                    'psAccountsUpdateLink' => null,

                    ////////////////////////////
                    // PsAccountsPresenter

                    // FIXME :  Mix "SSO user" with "Backend user"
                    'user' => [
                        'uuid' => $this->psAccountsService->getUserUuid() ?: null,
                        'email' => $this->psAccountsService->getEmail() ?: null,
                        'emailIsValidated' => $this->psAccountsService->isEmailValidated(),
                        'isSuperAdmin' => $shopContext->getContext()->employee->isSuperAdmin(),
                    ],
                    'backendUser' => [
                        'email' => $shopContext->getContext()->employee->email,
                        'employeeId' => $shopContext->getContext()->employee->id,
                        'isSuperAdmin' => $shopContext->getContext()->employee->isSuperAdmin(),
                    ],
                    'currentShop' => $this->shopProvider->getCurrentShop($psxName),
                    'isShopContext' => $shopContext->isShopContext(),
                    'superAdminEmail' => $this->psAccountsService->getSuperAdminEmail(),

                    // TODO: link to a page to display an "Update Your PSX" notice
                    'onboardingLink' => $onboardingLink,

                    'ssoResendVerificationEmail' => $this->module->getParameter('ps_accounts.sso_resend_verification_email_url'),
                    'manageAccountLink' => $this->module->getSsoAccountUrl(),

                    'isOnboardedV4' => $this->psAccountsService->isAccountLinkedV4(),

                    'shops' => $this->shopProvider->getShopsTree($psxName),
                    'adminAjaxLink' => $this->psAccountsService->getAdminAjaxUrl(),

                    'accountsUiUrl' => $this->module->getParameter('ps_accounts.accounts_ui_url'),
                ],
                (new DependenciesPresenter())->present($psxName)
            );
        } catch (\Exception $e) {
            SentryService::capture($e);
        }

        return [];
    }
}

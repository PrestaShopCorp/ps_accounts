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
use PrestaShop\Module\PsAccounts\Handler\Error\Sentry;
use PrestaShop\Module\PsAccounts\Installer\Installer;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;
use PrestaShop\Module\PsAccounts\Service\SsoService;

/**
 * Construct the psaccounts module.
 */
class PsAccountsPresenter
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
     * @var SsoService
     */
    protected $ssoService;

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
     * PsAccountsPresenter constructor.
     *
     * @param PsAccountsService $psAccountsService
     * @param ShopProvider $shopProvider
     * @param ShopLinkAccountService $shopLinkAccountService
     * @param SsoService $ssoService
     * @param Installer $installer
     * @param ConfigurationRepository $configuration
     */
    public function __construct(
        PsAccountsService $psAccountsService,
        ShopProvider $shopProvider,
        ShopLinkAccountService $shopLinkAccountService,
        SsoService $ssoService,
        Installer $installer,
        ConfigurationRepository $configuration
    ) {
        $this->psAccountsService = $psAccountsService;
        $this->shopProvider = $shopProvider;
        $this->shopLinkAccountService = $shopLinkAccountService;
        $this->ssoService = $ssoService;
        $this->installer = $installer;
        $this->configuration = $configuration;
    }

    /**
     * Present the PsAccounts module data for JS
     *
     * @param $psxName
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function present($psxName)
    {
        // FIXME : Do this elsewhere
        $this->shopLinkAccountService->manageOnboarding();

        $shopContext = $this->shopProvider->getShopContext();

        $isEnabled = false; //$this->installer->isEnabled('ps_accounts');

        try {
            return [
                'psxName' => $psxName,
                'psIs17' => $shopContext->isShop17(),

                // FIXME : Installed status of module itself
                'psAccountsIsInstalled' => true,
                'psAccountsInstallLink' => null,

                // Enable status
                'psAccountsIsEnabled' => $isEnabled,
                'psAccountsEnableLink' => ($isEnabled ? null : $this->installer->getEnableUrl('ps_accounts', $psxName)),

                'onboardingLink' => $this->shopLinkAccountService->getLinkAccountUrl($psxName),

                // FIXME :  Mix "SSO user" with "Backend user"
                'user' => [
                    'email' => $this->configuration->getFirebaseEmail() ?: null,
                    'emailIsValidated' => $this->configuration->firebaseEmailIsVerified(),
                    'isSuperAdmin' => $shopContext->getContext()->employee->isSuperAdmin(),
                ],

                'currentShop' => $this->shopProvider->getCurrentShop($psxName),
                'isShopContext' => $shopContext->isShopContext(),
                'shops' => $this->shopProvider->getShopsTree($psxName),

                'superAdminEmail' => $this->psAccountsService->getSuperAdminEmail(),

                // FIXME : move into Vue components .env
                'ssoResendVerificationEmail' => $this->ssoService->getSsoResendVerificationEmailUrl(),
                'manageAccountLink' => $this->ssoService->getSsoAccountUrl(),

                'adminAjaxLink' => $this->psAccountsService->getAdminAjaxUrl(),

                // EventBus dependency
                'psEventbusStatus' => [
                    'isInstalled' => $this->installer->isInstalled('ps_eventbus'),
                    'installLink' => $this->installer->getInstallUrl('ps_eventbus', $psxName),
                    'isEnabled' => $this->installer->isEnabled('ps_eventbus'),
                    'enableLink' => $this->installer->getEnableUrl('ps_eventbus', $psxName),
                ],
            ];
        } catch (\Exception $e) {
            Sentry::captureAndRethrow($e);
        }

        return [];
    }
}

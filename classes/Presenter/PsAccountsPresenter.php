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
use PrestaShop\Module\PsAccounts\Installer\Installer;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;
use PrestaShop\Module\PsAccounts\Service\SsoService;
use Ps_accounts;

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
     * @var ErrorHandler
     */
    private $errorHandler;

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
     * @param ErrorHandler $errorHandler
     */
    public function __construct(
        PsAccountsService $psAccountsService,
        ShopProvider $shopProvider,
        ShopLinkAccountService $shopLinkAccountService,
        SsoService $ssoService,
        Installer $installer,
        ConfigurationRepository $configuration,
        ErrorHandler $errorHandler
    ) {
        $this->psAccountsService = $psAccountsService;
        $this->shopProvider = $shopProvider;
        $this->shopLinkAccountService = $shopLinkAccountService;
        $this->ssoService = $ssoService;
        $this->installer = $installer;
        $this->configuration = $configuration;
        $this->errorHandler = $errorHandler;
    }

    /**
     * Present the PsAccounts module data for JS
     *
     * @param $psxName
     *
     * @return array
     *
     * @throws \Exception
     */
    public function present($psxName)
    {
        // FIXME : Do this elsewhere
        $this->shopLinkAccountService->manageOnboarding();

        $shopContext = $this->shopProvider->getShopContext();

        try {
            return [
                'psxName' => $psxName,
                'psIs17' => $shopContext->isShop17(),

                // FIXME : Installed status of module itself
                'psAccountsIsInstalled' => Module::isInstalled('ps_accounts'),
                'psAccountsInstallLink' => $this->installer->getPsAccountsInstallLink($psxName),

                // Enable status
                'psAccountsIsEnabled' => Module::isEnabled('ps_accounts'),
                'psAccountsEnableLink' => $this->installer->getPsAccountsEnableLink($psxName),

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
            ];
        } catch (\Exception $e) {
            $this->errorHandler->handle($e, $e->getCode());
        }

        return [];
    }
}

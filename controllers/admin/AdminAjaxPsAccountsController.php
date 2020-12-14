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

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\ShopKeysService;

/**
 * Controller for all ajax calls.
 */
class AdminAjaxPsAccountsController extends ModuleAdminController
{
    const STR_TO_SIGN = 'data';

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var Configuration
     */
    private $configurationAdapter;

    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    /**
     * AdminAjaxPsAccountsController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = $this->module->getService(ConfigurationRepository::class);
        $this->configurationAdapter = $this->module->getService('ps_accounts.configuration');
        $this->psAccountsService = $this->module->getService(PsAccountsService::class);
        $this->errorHandler = $this->module->getService(ErrorHandler::class);
    }

    /**
     * AJAX: Generate ssh key.
     *
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGenerateSshKey()
    {
        try {
            $sshKey = new ShopKeysService();
            $key = $sshKey->createPair();
            $this->configuration->updateAccountsRsaPrivateKey($key['privatekey']);
            $this->configuration->updateAccountsRsaPublicKey($key['publickey']);
            $data = 'data';
            $this->configuration->updateAccountsRsaSignData(
                $sshKey->signData(
                    $this->configuration->getAccountsRsaPrivateKey(),
                    self::STR_TO_SIGN
                )
            );

            $this->ajaxDie(
                json_encode($this->configuration->getAccountsRsaPublicKey())
            );
        } catch (Exception $e) {
            $this->errorHandler->handle($e, $e->getCode());
        }
    }

    /**
     * FIXME: remove this ajax call if still used anywhere
     *
     * AJAX: Save Admin Token.
     *
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessSaveAdminToken()
    {
        try {
            if (false === $this->configurationAdapter->get(Configuration::PS_PSX_FIREBASE_ADMIN_TOKEN)) {
                $this->configurationAdapter->set(Configuration::PS_PSX_FIREBASE_ADMIN_TOKEN, Tools::getValue('adminToken'));
            }
            $this->configurationAdapter->set(Configuration::PS_ACCOUNTS_FIREBASE_ADMIN_TOKEN, Tools::getValue('adminToken'));

            $this->ajaxDie(
                json_encode(true)
            );
        } catch (Exception $e) {
            $this->errorHandler->handle($e, $e->getCode());
        }
    }

    /**
     * AJAX: Save Admin Token.
     *
     * @return void
     *
     * @throws Exception
     */
    public function ajaxEmailIsVerifiedToken()
    {
        try {
            $this->ajaxDie(
                json_encode($this->configuration->firebaseEmailIsVerified())
            );
        } catch (Exception $e) {
            $this->errorHandler->handle($e, $e->getCode());
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGetOrRefreshToken()
    {
        try {
            header('Content-Type: text/json');

            $this->ajaxDie(
                json_encode([
                    'token' => $this->psAccountsService->getOrRefreshToken(),
                    'refreshToken' => $this->psAccountsService->getFirebaseRefreshToken(),
                ])
            );
        } catch (Exception $e) {
            $this->errorHandler->handle($e, $e->getCode());
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    //public function displayAjaxUnlinkShop()
    public function ajaxProcessUnlinkShop()
    {
        try {
            $response = $this->psAccountsService->unlinkShop();

            http_response_code($response['httpCode']);

            header('Content-Type: text/json');

            $this->ajaxDie(json_encode($response['body']));
        } catch (Exception $e) {
            $this->errorHandler->handle($e, $e->getCode());
        }
    }
}

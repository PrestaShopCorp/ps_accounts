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

use PrestaShop\AccountsAuth\DependencyInjection\DependencyContainer;
use PrestaShop\AccountsAuth\Handler\Error\ErrorHandler;
use PrestaShop\AccountsAuth\Repository\ConfigurationRepository;
use PrestaShop\AccountsAuth\Service\SshKey;

/**
 * Controller for all call ajax.
 */
class AdminAjaxPsAccountsController extends ModuleAdminController
{
    const STR_TO_SIGN = 'data';

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * AdminAjaxPsAccountsController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = DependencyContainer::getInstance()->get(ConfigurationRepository::class);
    }

    /**
     * AJAX: Generate ssh key.
     *
     * @return void
     */
    public function ajaxProcessGenerateSshKey()
    {
        try {
            $sshKey = new SshKey();
            $key = $sshKey->generate();
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
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->handle($e, $e->getCode());
        }
    }

    /**
     * AJAX: Save Admin Token.
     *
     * @return void
     */
    public function ajaxProcessSaveAdminToken()
    {
        try {
            // FIXME : what for ?
            Configuration::updateValue('PS_PSX_FIREBASE_ADMIN_TOKEN', Tools::getValue('adminToken'));

            $this->ajaxDie(
                json_encode(true)
            );
        } catch (Exception $e) {
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->handle($e, $e->getCode());
        }
    }

    /**
     * AJAX: Save Admin Token.
     *
     * @return void
     */
    public function ajaxEmailIsVerifiedToken()
    {
        try {
            $this->ajaxDie(
                json_encode($this->configuration->firebaseEmailIsVerified())
            );
        } catch (Exception $e) {
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->handle($e, $e->getCode());
        }
    }
}

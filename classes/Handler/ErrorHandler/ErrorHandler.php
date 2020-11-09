<?php
/**
 * 2007-2020 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Handler\ErrorHandler;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use Raven_Client;

/**
 * Handle Error.
 */
class ErrorHandler
{
    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * @var Raven_Client
     */
    protected $client;

    /**
     * ErrorHandler constructor.
     *
     * @param PsAccountsService $psAccountsService
     * @param string $sentryCredentials
     *
     * @throws \Raven_Exception
     */
    public function __construct(
        PsAccountsService $psAccountsService,
        $sentryCredentials
    ) {
        $this->psAccountsService = $psAccountsService;

        $this->client = new Raven_Client(
            $sentryCredentials,
            [
                'level' => 'warning',
                'tags' => [
                    'php_version' => phpversion(),
                    'ps_accounts_version' => \Ps_accounts::VERSION,
                    'prestashop_version' => _PS_VERSION_,
                    'ps_accounts_is_enabled' => \Module::isEnabled('ps_accounts'),
                    'ps_accounts_is_installed' => \Module::isInstalled('ps_accounts'),
                    'email' => $this->psAccountsService->getEmail(),
                    Configuration::PS_ACCOUNTS_FIREBASE_ID_TOKEN => $this->psAccountsService->getFirebaseIdToken(),
                    Configuration::PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN => $this->psAccountsService->getFirebaseRefreshToken(),
                    Configuration::PSX_UUID_V4 => $this->psAccountsService->getShopUuidV4(),
                    Configuration::PS_ACCOUNTS_FIREBASE_EMAIL_IS_VERIFIED => $this->psAccountsService->isEmailValidated(),
                    Configuration::PS_ACCOUNTS_FIREBASE_EMAIL => $this->psAccountsService->getEmail(),
                    Configuration::PS_ACCOUNTS_RSA_PUBLIC_KEY => $this->psAccountsService->getAccountsRsaPublicKey(),
                    Configuration::PS_ACCOUNTS_RSA_SIGN_DATA => $this->psAccountsService->getAccountsRsaSignData(),
                ],
            ]
        );

        $this->client->install();
    }

    /**
     * @param \Exception $error
     * @param mixed $code
     * @param bool|null $throw
     *
     * @return void
     *
     * @throws \Exception
     */
    public function handle($error, $code = null, $throw = true)
    {
        $code ? $this->client->captureException($error) : $this->client->captureMessage($error);
        if ($code && true === $throw) {
            http_response_code($code);
            throw $error;
        }
    }

    /**
     * @return void
     */
    private function __clone()
    {
    }
}

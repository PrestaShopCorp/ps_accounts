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

use PrestaShop\Module\PsAccounts\Api\Client\ServicesAccountsClient;
use PrestaShop\Module\PsAccounts\Exception\WebhookException;
use PrestaShop\Module\PsAccounts\Handler\Error\Sentry;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\WebHook\Validator;

class ps_accountsDispatchWebHookModuleFrontController extends ModuleFrontController
{
    const PS_CHECKOUT_SHOP_UUID_V4 = 'PS_CHECKOUT_SHOP_UUID_V4';

    /**
     * @var Ps_accounts
     */
    public $module;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * Id coming from PSL
     *
     * @var string
     */
    private $shopId;

    /**
     * Id coming from Paypal
     *
     * @var string
     */
    private $merchantId;

    /**
     * Id coming from Firebase
     *
     * @var string
     */
    private $firebaseId;

    /**
     * ps_accountsDispatchWebHookModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = $this->module->getService(ConfigurationRepository::class);
    }

    /**
     * Initialize the webhook script
     *
     * @return void
     *
     * @throws Throwable
     */
    public function display()
    {
        $validator = new Validator(
            $this->module->getService(ServicesAccountsClient::class),
            $this->configuration,
            $this->module->getService('ps_accounts.context')
        );

        try {
            $headers = getallheaders();
            $body = json_decode(file_get_contents('php://input'), true);

            $validator->validate(
                $headers,
                $body
            );

            $this->generateHttpResponse(
                $this->dispatchWebhook($headers, $body)
            );
        } catch (\Exception $e) {
            Sentry::captureAndRethrow($e);
        }
    }

    /**
     * Dispatch webhook to service (or fallback here for 'accounts' service)
     *
     * @param array $bodyValues
     *
     * @return array
     *
     * @throws WebhookException
     * @throws PrestaShopException
     */
    private function dispatchWebhook(array $headers, array $bodyValues)
    {
        $moduleName = $bodyValues['service'];
        if ($moduleName && $moduleName !== 'ps_accounts') {
            /** @var Module $module */
            $module = Module::getInstanceByName($moduleName);

            $error = Hook::exec(
                'receiveWebhook_' . $moduleName,
                ['headers' => $headers, 'body' => $bodyValues],
                $module->id
            );

            if ($error === '') {
                return [
                    'status_code' => 200,
                    'message' => 'ok',
                ];
            }
            throw new WebhookException($error);
        } else {
            return $this->receiveAccountsWebhook($headers, $bodyValues);
        }
    }

    /**
     * Override displayMaintenancePage to prevent the maintenance page to be displayed
     *
     * @return void
     */
    protected function displayMaintenancePage()
    {
    }

    /**
     * Override geolocationManagement to prevent country GEOIP blocking
     *
     * @param Country $defaultCountry
     *
     * @return false
     */
    protected function geolocationManagement($defaultCountry)
    {
        return false;
    }

    /**
     * @param array $headers
     * @param array $body
     *
     * @return array
     */
    private function receiveAccountsWebhook($headers, $body)
    {
        switch ($body['action']) {
            case 'EmailVerified':
                $this->configuration->updateFirebaseEmailIsVerified(true);

                return [
                    'status_code' => 200,
                    'message' => 'ok',
                ];

            // TODO : Other cases

            default: // unknown action
                return [
                    'status_code' => 500,
                    'message' => 'Action unknown',
                ];
        }
    }

    /**
     * @param array $output
     *
     * @return void
     */
    private function generateHttpResponse(array $output)
    {
        header('Content-type: application/json');
        http_response_code($output['status_code']);
        echo json_encode($output['message']);
        exit;
    }
}

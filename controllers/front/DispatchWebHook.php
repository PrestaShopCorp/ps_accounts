<?php
/**
 * 2007-2020 PrestaShop and Contributors
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

use PrestaShop\AccountsAuth\DependencyInjection\PsAccountsServiceProvider;
use PrestaShop\AccountsAuth\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\AccountsAuth\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Api\ServicesApi\Webhook;
use PrestaShop\Module\PsAccounts\Exception\WebhookException;
use PrestaShop\Module\PsAccounts\WebHook\Validator;

class ps_accountsDispatchWebHookModuleFrontController extends FrontController
{
    const PS_CHECKOUT_SHOP_UUID_V4 = 'PS_CHECKOUT_SHOP_UUID_V4';

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * ps_accountsDispatchWebHookModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = PsAccountsServiceProvider::getInstance()->get(ConfigurationRepository::class);
    }

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
     * Initialize the webhook script
     *
     * @return void
     *
     * @throws Exception
     */
    public function display()
    {
        $validator = new Validator();
        try {
            $headers = getallheaders();
            $body = json_decode(file_get_contents('php://input'), true);

            $validator->validate(
                $headers,
                $body
            );

            return $this->generateHttpResponse(
                $this->dispatchWebhook($headers, $body)
            );
        } catch (\Exception $e) {
            $errorHandler = ErrorHandler::getInstance();
            $errorHandler->handle($e, $e->getCode());
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
     * @throws ReflectionException
     */
    private function dispatchWebhook(array $headers, array $bodyValues)
    {
        $moduleName = $bodyValues['service'];
        if ($moduleName !== 'ps_accounts') {
            /** @var Module $module */
            $module = Module::getInstanceByName($moduleName);

            $error = \Hook::exec(
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
            throw new WebhookException($error, 500);
        } else {
            return $this->receiveAccountsWebhook($headers, $bodyValues);
        }
    }

    /**
     * @param array $headers
     * @param array $body
     *
     * @return array
     *
     * @throws ReflectionException
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
}

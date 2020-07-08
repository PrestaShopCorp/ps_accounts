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
use PrestaShop\Module\PsAccounts\Api\ServicesApi\Webhook;
use PrestaShop\Module\PsAccounts\WebHook\Validator;

class ps_accountsDispatchWebHookModuleFrontController extends FrontController
{
    const PS_CHECKOUT_SHOP_UUID_V4 = 'PS_CHECKOUT_SHOP_UUID_V4';

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
            return $this->generateHttpResponse([
                'status_code' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispatch webhook to service (or fallback here for 'accounts' service)
     *
     * @param array $bodyValues
     *
     * @return array
     */
    private function dispatchWebhook(array $headers, array $bodyValues)
    {
        $moduleName = $bodyValues['service'];
        if ($moduleName !== 'ps_accounts') {
            $error = \Hook::exec(
                'receiveWebhook_' . $moduleName,
                ['headers' => $headers, 'body' => $bodyValues],
                Module::getInstanceByName($moduleName)->id
            );

            if ($error === '') {
                return [
                    'status_code' => 200,
                    'message' => 'ok',
                ];
            }
            throw new Exception($error);
        } else {
            return $this->receiveAccountsWebhook($headers, $bodyValues);
        }
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
                Configuration::updateValue('PS_ACCOUNTS_EMAIL_VERIFIED', true);

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

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

namespace PrestaShop\Module\PsAccounts\WebHook;

use PrestaShop\AccountsAuth\DependencyInjection\PsAccountsServiceProvider;
use PrestaShop\AccountsAuth\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Api\AccountsClient;
use PrestaShop\Module\PsAccounts\Exception\FirebaseException;
use PrestaShop\Module\PsAccounts\Exception\WebhookException;

class Validator
{
    const HEADER_DATA_ERROR = 'CorrelationId in headers can\'t be empty';
    const BODY_ERROR = 'Body can\'t be empty';
    const BODY_SERVICE_ERROR = 'Service can\'t be empty';
    const BODY_ACTION_ERROR = 'Action can\'t be empty';
    const BODY_DATA_ERROR = 'Data can\'t be empty';
    const BODY_DATA_OWNERID_ERROR = 'OwnerId can\'t be empty';
    const BODY_DATA_SHOPID_ERROR = 'ShopId can\'t be empty';
    const BODY_OTHER_ERROR = 'ShopId can\'t be empty';

    /**
     * @var int
     */
    private $statusCode = 200;

    /**
     * @var string
     */
    private $message = '';

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * Validator constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->context = \Context::getContext();

        $this->configuration = PsAccountsServiceProvider::getInstance()->get(ConfigurationRepository::class);
    }

    /**
     * Validates the webHook data
     *
     * @param array $headerValues
     * @param array $payload
     *
     * @return array
     */
    public function validateData($headerValues = [], $payload = [])
    {
        // TODO check empty
        return array_merge(
            $this->validateHeaderData($headerValues),
            $this->validateBodyData($payload)
        );
    }

    /**
     * Validates the webHook header data
     *
     * @param array $headerValues
     *
     * @return array
     */
    public function validateHeaderData(array $headerValues)
    {
        $errors = [];
        if (empty($headerValues['correlationId'])) {
            $errors[] = self::HEADER_DATA_ERROR;
        }

        return $errors;
    }

    /**
     * Validates the webHook body data
     *
     * @param array $payload
     *
     * @return array
     */
    public function validateBodyData(array $payload)
    {
        $errors = [];
        if (empty($payload)) {
            $errors[] = self::BODY_ERROR;
        }

        if (empty($payload['service'])) {
            $errors[] = self::BODY_SERVICE_ERROR;
        }

        if (empty($payload['action'])) {
            $errors[] = self::BODY_ACTION_ERROR;
        }

        if (empty($payload['data'])) {
            $errors[] = self::BODY_DATA_ERROR;
        }

        if (empty($payload['data']['ownerId'])) {
            $errors[] = self::BODY_DATA_OWNERID_ERROR;
        }
        if (empty($payload['data']['shopId'])) {
            $errors[] = self::BODY_DATA_SHOPID_ERROR;
        }

        return $errors;
    }

    /**
     * Validates the webHook data
     *
     * @param array $headerValues
     * @param array $bodyValues
     *
     * @return void
     *
     * @throws WebhookException
     * @throws FirebaseException
     */
    public function validate($headerValues = [], $bodyValues = [])
    {
        $errors = $this->validateData($headerValues, $bodyValues);
        // No verifyWebhook if data validation fails.
        $errors = empty($errors) ? $this->verifyWebhook($headerValues, $bodyValues) : $errors;

        if (!empty($errors)) {
            throw new WebhookException((string) json_encode($errors), 500);
        }
    }

    /**
     * Check the IP whitelist and Shop, Merchant and Psx Ids
     *
     * @param array $shopId
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function checkExecutionPermissions($shopId)
    {
        $dbShopId = $this->configuration->getShopUuid();
        if ($shopId != $dbShopId) {
            $output = [
                'status_code' => 500,
                'message' => 'ShopId don\'t match. You aren\'t authorized',
            ];
        }

        return true;
    }

    /**
     * Check if the Webhook comes from the PSL
     *
     * @param array $headerValues
     * @param array $bodyValues
     *
     * @return array
     *
     * @throws FirebaseException
     */
    private function verifyWebhook(array $headerValues = [], array $bodyValues = [])
    {
        $response = (new AccountsClient($this->context->link))->checkWebhookAuthenticity($headerValues, $bodyValues);

        if (!$response || 200 > $response['httpCode'] || 299 < $response['httpCode']) {
            return [$response['body'] ? $response['body'] : 'Webhook not verified'];
        }

        return [];
    }
}

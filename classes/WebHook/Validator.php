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

namespace PrestaShop\Module\PsAccounts\WebHook;

use Context;
use PrestaShop\Module\PsAccounts\Api\Client\ServicesAccountsClient;
use PrestaShop\Module\PsAccounts\Exception\WebhookException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

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
     * @var Context
     */
    private $context;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var ServicesAccountsClient
     */
    private $accountsClient;

    /**
     * Validator constructor.
     *
     * @param ServicesAccountsClient $accountsClient
     * @param ConfigurationRepository $configuration
     * @param Context $context
     */
    public function __construct(
        ServicesAccountsClient $accountsClient,
        ConfigurationRepository $configuration,
        Context $context
    ) {
        $this->accountsClient = $accountsClient;

        $this->configuration = $configuration;

        $this->context = $context;
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
     * @throws \PrestaShopException
     */
    public function validate($headerValues = [], $bodyValues = [])
    {
        $errors = $this->validateData($headerValues, $bodyValues);
        // No verifyWebhook if data validation fails.
        $errors = empty($errors) ? $this->verifyWebhook($headerValues, $bodyValues) : $errors;

        if (!empty($errors)) {
            throw new WebhookException((string) json_encode($errors));
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
     * @throws \PrestaShopException
     */
    private function verifyWebhook(array $headerValues = [], array $bodyValues = [])
    {
        //$response = (new AccountsClient($this->context->link))->checkWebhookAuthenticity($headerValues, $bodyValues);

        $response = $this->accountsClient->verifyWebhook($headerValues, $bodyValues);

        if (!$response || 200 > $response['httpCode'] || 299 < $response['httpCode']) {
            return [$response['body'] ? $response['body'] : 'Webhook not verified'];
        }

        return [];
    }
}

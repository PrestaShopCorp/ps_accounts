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

namespace PrestaShop\Module\PsAccounts\Service\Accounts;

use PrestaShop\Module\PsAccounts\Http\Client\Response;

class AccountsException extends \Exception
{
    const ERROR_STORE_LEGACY_NOT_FOUND = 'store-identity/store-legacy-not-found';

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var string
     */
    protected $errorCode;

    /**
     * @var string
     */
    protected $reason;

    /**
     * @var Response|null
     */
    protected $storeResponse;

    /**
     * @param Response $response
     * @param string $defaultMessage
     * @param string $defaultErrorCode
     */
    public function __construct($response, $defaultMessage = '', $defaultErrorCode = '')
    {
        $this->response = $response;
        $this->errorCode = $response->getErrorCodeFromBody('error', $defaultErrorCode);
        $this->reason = $this->getReasonFromResponseBody();
        $this->storeResponse = $this->getStoreResponseFromResponseBody();

        parent::__construct($response->getErrorMessageFromBody('message', $defaultMessage));
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the error code.
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return Response|null
     */
    public function getStoreResponse()
    {
        return $this->storeResponse;
    }

    /**
     * @return string
     */
    public function getDetails()
    {
        if ($storeResponse = $this->getStoreResponse()) {
            return $storeResponse->statusCode . ' ' . print_r($storeResponse->raw, true);
        } elseif ($reason = $this->getReason()) {
            return $reason;
        }

        return '';
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function getReasonFromResponseBody($key = 'reason')
    {
        return $this->response->getValueFromBody(
            $key,
            null,
            static function ($value) {
                return is_string($value);
            }
        );
    }

    /**
     * @param string $key
     *
     * @return Response|null
     */
    protected function getStoreResponseFromResponseBody($key = 'response')
    {
        $response = $this->response->getValueFromBody(
            $key,
            null,
            static function ($value) {
                return is_array($value);
            }
        );

        if ($response) {
            return new Response(
                isset($response['data']) ? $response['data'] : [],
                isset($response['status']) ? $response['status'] : 0,
                isset($response['headers']) ? $response['headers'] : []
            );
        }

        return null;
    }
}

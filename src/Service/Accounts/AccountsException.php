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
    /**
     * @var string
     */
    protected $errorCode;

    /**
     * @param Response $response
     * @param string $defaultMessage
     * @param string $defaultErrorCode
     */
    public function __construct($response, $defaultMessage = '', $defaultErrorCode = '')
    {
        $this->errorCode = $this->getErrorCodeFromResponse($response, $defaultErrorCode);

        parent::__construct($this->getErrorMessageFromResponse($response, $defaultMessage));
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
     * @param Response $response
     * @param string $defaultMessage
     *
     * @return string
     */
    protected function getErrorMessageFromResponse(Response $response, $defaultMessage = '')
    {
        if (!isset($response->body['message'])) {
            return $defaultMessage;
        }

        return $response->body['message'];
    }

    /**
     * @param Response $response
     * @param string $defaultCode
     *
     * @return string
     */
    protected function getErrorCodeFromResponse(Response $response, $defaultCode = '')
    {
        if (!isset($response->body['error'])) {
            return $defaultCode;
        }

        return $response->body['error'];
    }
}

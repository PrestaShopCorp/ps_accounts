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

namespace PrestaShop\Module\PsAccounts\Service\OAuth2;

use PrestaShop\Module\PsAccounts\Http\Client\Response;

class OAuth2ServerException extends OAuth2Exception
{
    const ERROR_INVALID_REQUEST = 'invalid_request';
    const ERROR_INVALID_SCOPE = 'invalid_scope';

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
        $this->errorCode = $response->getErrorCodeFromBody('error', $defaultErrorCode);

        parent::__construct($response->statusCode . ': ' . $response->getErrorMessageFromBody('error_description', $defaultMessage));
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
}

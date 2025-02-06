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

namespace PrestaShop\Module\PsAccounts\Api\Controller;

use Context;
use PrestaShop\Module\PsAccounts\Api\Controller\Exception\HttpException;
use PrestaShop\Module\PsAccounts\Api\Controller\Exception\UnauthorizedException;
use PrestaShop\Module\PsAccounts\OAuth2\ApiClient;
use PrestaShop\Module\PsAccounts\OAuth2\Token\Validator\Exception\AudienceInvalidException;
use PrestaShop\Module\PsAccounts\OAuth2\Token\Validator\Exception\ScopeInvalidException;
use PrestaShop\Module\PsAccounts\OAuth2\Token\Validator\Exception\SignatureInvalidException;
use PrestaShop\Module\PsAccounts\OAuth2\Token\Validator\Exception\TokenExpiredException;
use PrestaShop\Module\PsAccounts\OAuth2\Token\Validator\Exception\TokenInvalidException;
use PrestaShop\Module\PsAccounts\OAuth2\Token\Validator\Validator;
use PrestaShop\Module\PsAccounts\Service\SentryService;

abstract class AbstractV2RestController extends AbstractRestController
{
    /**
     * @var object
     */
    protected $token;

    /**
     * @var Validator
     */
    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator(
            $this->module->getService(ApiClient::class)
        );
    }

    /**
     * Controller level scopes
     *
     * @return array
     */
    protected function getScope()
    {
        return [];
    }

    /**
     * Controller level audiences
     *
     * @return array
     */
    protected function getAudience()
    {
        return [];
    }

    /**
     * Controller's entry point
     *
     * @return void
     *
     * @throws \PrestaShopException
     */
    // public function init()
    // public function displayAjax()
    public function postProcess()
    {
        try {
            if ($this->authenticated) {
                $this->checkAuthorization();
            }

            $payload = $this->decodePayload();
            $httpMethod = $this->extractMethod($payload);

            $this->dispatchVerb($httpMethod, $payload);
        } catch (\Throwable $e) {
            $this->handleException($e);
            /* @phpstan-ignore-next-line */
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * @param null $defaultShopId
     *
     * @return array
     */
    protected function decodePayload($defaultShopId = null)
    {
        if ($defaultShopId === null) {
            $defaultShopId = Context::getContext()->shop->id;
        }

        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'], true)) {
            // FIXME To avoid this warning set 'always_populate_raw_post_data' to '-1' in php.ini and use the php://input stream instead. in Unknown on line 0"
            $json = file_get_contents('php://input');
            $payload = !empty($json) ? json_decode($json, true) : [];
        } else {
            $payload = $_GET;
        }

        if (!isset($payload['shop_id'])) {
            $payload['shop_id'] = $defaultShopId;
        }

        return $this->initShopContext($payload, $defaultShopId);
    }

    /**
     * @param array $payload
     *
     * @return array
     */
    protected function initShopContext(array $payload)
    {
        $shop = new \Shop((int) $payload['shop_id']);
        if ($shop->id) {
            $this->setContextShop($shop);
        }

        return $payload;
    }

    /**
     * @param array $payload
     *
     * @return mixed
     */
    protected function extractMethod(array &$payload)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        // detect method from payload (hack with some shop server configuration)
        if (isset($payload['method'])) {
            $method = $payload['method'];
            unset($payload['method']);
        }

        return $method;
    }

//    /**
//     * @return bool
//     */
//    protected function displayMaintenancePage()
//    {
//        return false;
//    }

    /**
     * @return true
     *
     * @throws UnauthorizedException
     */
    protected function checkAuthorization()
    {
        $authorizationHeader = $this->getRequestHeader('Authorization');
        if (!isset($authorizationHeader)) {
            throw new UnauthorizedException('Authorization header is required.');
        }

        $jwtString = trim(str_replace('Bearer', '', $authorizationHeader));

        $errorMsg = 'Invalid token';
        try {
            $this->token = $this->validator->validateToken($jwtString, $this->getScope(), $this->getAudience());

            return true;
        } catch (SignatureInvalidException $e) {
        } catch (AudienceInvalidException $e) {
            $errorMsg = 'Invalid audience';
        } catch (ScopeInvalidException $e) {
            $errorMsg = 'Invalid scope';
        } catch (TokenExpiredException $e) {
        } catch (TokenInvalidException $e) {
        }
        $this->module->getLogger()->error($e);

        throw new UnauthorizedException($errorMsg, 0, $e);
    }

    /**
     * Method level scope checking
     *
     * @param array $scope
     *
     * @return void
     *
     * @throws UnauthorizedException
     */
    protected function assertScope(array $scope)
    {
        if (!$this->authenticated) {
            return;
        }

        try {
            $this->validator->validateScope($this->token, $scope);
        } catch (ScopeInvalidException $e) {
            $this->module->getLogger()->error($e);

            throw new UnauthorizedException('Invalid scope', 0, $e);
        }
    }

    /**
     * Method level audience checking
     *
     * @param array $audience
     *
     * @return void
     *
     * @throw UnauthorizedException
     */
    protected function assertAudience(array $audience)
    {
        if (!$this->authenticated) {
            return;
        }

        try {
            $this->validator->validateAudience($this->token, $audience);
        } catch (AudienceInvalidException $e) {
            $this->module->getLogger()->error($e);

            throw new UnauthorizedException('Invalid audience', 0, $e);
        }
    }

    /**
     * @param \Throwable|\Exception $e
     * @param string|null $message
     *
     * @return void
     */
    protected function handleException($e, $message = null)
    {
        if ($e instanceof HttpException) {
            $this->module->getLogger()->error($e);

            $this->dieWithResponseJson([
                'error' => true,
                'message' => $message ?: $e->getMessage(),
            ], $e->getStatusCode());
        } else {
            SentryService::capture($e);

            $this->dieWithResponseJson([
                'error' => true,
                'message' => $message ?: 'Failed processing your request',
            ], 500);
        }
    }
}

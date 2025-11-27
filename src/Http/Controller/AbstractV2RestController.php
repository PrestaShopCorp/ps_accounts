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

namespace PrestaShop\Module\PsAccounts\Http\Controller;

use Context;
use ModuleFrontController;
use PrestaShop\Module\PsAccounts\Http\Exception\HttpException;
use PrestaShop\Module\PsAccounts\Http\Exception\MethodNotAllowedException;
use PrestaShop\Module\PsAccounts\Http\Exception\UnauthorizedException;
use PrestaShop\Module\PsAccounts\Polyfill\Traits\Controller\AjaxRender;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\AudienceInvalidException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\ScopeInvalidException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\SignatureInvalidException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\TokenExpiredException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Exception\TokenInvalidException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator\Validator;
use PrestaShop\Module\PsAccounts\Service\SentryService;
use ReflectionException;
use ReflectionParameter;

abstract class AbstractV2RestController extends ModuleFrontController
{
    use AjaxRender;
    use GetHeader;

    /**
     * Header to retrieve bearer from
     * FIXME: "Authorization" standard header might be filtered by server configuration
     */
    const HEADER_AUTHORIZATION = 'X-Prestashop-Authorization';

    /**
     * @var string
     */
    public $resourceId = 'id';

    /**
     * @var \Ps_accounts
     */
    public $module;

    /**
     * @var bool
     */
    protected $authenticated = true;

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

        $this->ajax = true;
        $this->content_only = true;
        $this->controller_type = 'module';
        $this->validator = $this->module->getService(Validator::class);
    }

    /**
     * Controller level scopes
     *
     * @return array
     */
    abstract public function getScope();

    /**
     * Controller level audiences
     *
     * @return array
     */
    abstract public function getAudience();

    /**
     * @return void
     */
    public function initContent()
    {
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
     * @param array $response
     * @param int|null $httpResponseCode
     *
     * @return void
     *
     * @throws \PrestaShopException
     */
    public function dieWithResponseJson(array $response, $httpResponseCode = null)
    {
        @ob_end_flush();
        @ob_end_clean();

        if (is_integer($httpResponseCode)) {
            http_response_code($httpResponseCode);
        }

        header('Content-Type: text/json');

        $this->ajaxRender((string) json_encode($response));
    }

    /**
     * @param string $httpMethod
     * @param array $payload
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function dispatchVerb($httpMethod, array $payload)
    {
        $id = array_key_exists($this->resourceId, $payload)
            ? $payload[$this->resourceId]
            : null;

        $statusCode = 200;

        switch ($httpMethod) {
            case 'GET':
                $method = null !== $id
                    ? RestMethod::SHOW
                    : RestMethod::INDEX;
                break;
            case 'POST':
                list($method, $statusCode) = null !== $id
                    ? [RestMethod::UPDATE, $statusCode]
                    : [RestMethod::STORE, 201];
                break;
            case 'PUT':
            case 'PATCH':
                $method = RestMethod::UPDATE;
                break;
            case 'DELETE':
                $statusCode = 204;
                $method = RestMethod::DELETE;
                break;
            default:
                throw new \Exception('Invalid Method : ' . $httpMethod);
        }

        $this->dieWithResponseJson($this->invokeMethod($method, $id, $payload), $statusCode);
    }

    /**
     * @param string $method
     * @param mixed $id
     * @param mixed $payload
     *
     * @return mixed
     */
    protected function invokeMethod($method, $id, $payload)
    {
        try {
            $method = new \ReflectionMethod($this, $method);
            $params = $method->getParameters();

            $args = [];

            if (null !== $id) {
                $args[] = $this->buildResource($id);
            }

            if (null !== $payload) {
                $args[] = $this->buildArg($payload, $params[1]);
            }

            return $method->invokeArgs($this, $args);
        } catch (ReflectionException $e) {
            throw new MethodNotAllowedException();
        }
    }

    /**
     * @param mixed $id
     *
     * @return mixed
     */
    protected function buildResource($id)
    {
        return $id;
    }

    /**
     * @param array $payload
     * @param ReflectionParameter $reflectionParam
     *
     * @return array|object
     *
     * @throws ReflectionException
     */
    protected function buildArg(array $payload, ReflectionParameter $reflectionParam)
    {
//        if ($reflectionParam->getType()->isBuiltin()) {
//            return $payload;
//        } else {
//            // Instantiate DTO like value bag
//            return $reflectionParam->getClass()->newInstance($payload);
//        }
        if ($reflectionParam->getClass()) {
            // Instantiate DTO like value bag
            return $reflectionParam->getClass()->newInstance($payload);
        }

        return $payload;
    }

    /**
     * Force shop context
     *
     * @param \Shop $shop
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function setContextShop(\Shop $shop)
    {
        /** @var ConfigurationRepository $conf */
        $conf = $this->module->getService(ConfigurationRepository::class);
        $conf->setShopId($shop->id);

        /** @var Context $context */
        $context = $this->module->getService('ps_accounts.context');
        $context->shop = $shop;
    }

    /**
     * @param int|null $defaultShopId
     *
     * @return array
     */
    protected function decodePayload($defaultShopId = null)
    {
        if ($defaultShopId === null) {
            $defaultShopId = Context::getContext()->shop->id;
        }

        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'], true)) {
            $decoded = json_decode(file_get_contents('php://input'), true);
            $payload = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
        } else {
            $payload = $_GET;
        }

        if (!isset($payload['shop_id'])) {
            $payload['shop_id'] = $defaultShopId;
        }

        return $this->initShopContext($payload);
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
    protected function extractMethod(array & $payload)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        // detect method from payload (hack with some shop server configuration)
        if (isset($payload['method'])) {
            $method = $payload['method'];
            unset($payload['method']);
        }

        return $method;
    }

    /**
     * @return true
     *
     * @throws UnauthorizedException
     */
    protected function checkAuthorization()
    {
        $authorizationHeader = $this->getRequestHeader(self::HEADER_AUTHORIZATION);
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

    /**
     * @return bool
     */
    protected function displayMaintenancePage()
    {
        return true;
    }

    /**
     * Override displayRestrictedCountryPage to prevent page country is not allowed
     *
     * @see FrontController::displayRestrictedCountryPage()
     *
     * @return void
     */
    protected function displayRestrictedCountryPage()
    {
    }

    /**
     * Override geolocationManagement to prevent country GEOIP blocking
     *
     * @see FrontController::geolocationManagement()
     *
     * @param \Country $defaultCountry
     *
     * @return false
     */
    protected function geolocationManagement($defaultCountry)
    {
        return false;
    }
}

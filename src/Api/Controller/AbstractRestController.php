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
use ModuleFrontController;
use PrestaShop\Module\PsAccounts\Exception\Http\HttpException;
use PrestaShop\Module\PsAccounts\Exception\Http\MethodNotAllowedException;
use PrestaShop\Module\PsAccounts\Exception\Http\UnauthorizedException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Polyfill\Traits\Controller\AjaxRender;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\SentryService;
use PrestaShop\OAuth2\Client\Provider\Exception\AudienceInvalidException;
use PrestaShop\OAuth2\Client\Provider\Exception\ScopeInvalidException;
use PrestaShop\OAuth2\Client\Provider\Exception\SignatureInvalidException;
use PrestaShop\OAuth2\Client\Provider\Exception\TokenExpiredException;
use PrestaShop\OAuth2\Client\Provider\Exception\TokenInvalidException;
use ReflectionException;
use ReflectionParameter;

abstract class AbstractRestController extends ModuleFrontController
{
    use AjaxRender;

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
     * @var ShopProvider
     */
    protected $oauth2Provider;

    public function __construct()
    {
        parent::__construct();

        $this->ajax = true;
        $this->content_only = true;
        $this->controller_type = 'module';

        $this->oauth2Provider = $this->module->getService(ShopProvider::class);
    }

    /**
     * @return void
     */
    public function initContent()
    {
    }

    /**
     * @return array
     */
    protected function getScope()
    {
        return [];
    }

    /**
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
            $this->dispatchVerb($this->decodeJsonPayload());
        } catch (HttpException $e) {
            $this->module->getLogger()->error($e);

            $this->dieWithResponseJson([
                'error' => true,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            $this->handleError($e);
            /* @phpstan-ignore-next-line */
        } catch (\Exception $e) {
            $this->handleError($e);
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
        ob_end_clean();

        if (is_integer($httpResponseCode)) {
            http_response_code($httpResponseCode);
        }

        header('Content-Type: text/json');

        $this->ajaxRender((string) json_encode($response));
    }

    /**
     * @param array $payload
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function dispatchVerb(array $payload)
    {
        $httpMethod = $this->extractMethod($payload);

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
        if ($reflectionParam->getClass()) {
            // Instantiate DTO like value bag
            return $reflectionParam->getClass()->newInstance($payload);
        }

        return $payload;
    }

    /**
     * @return array
     *
     * @throws UnauthorizedException
     * @throws TokenInvalidException
     */
    protected function decodeJsonPayload()
    {
        $defaultShopId = Context::getContext()->shop->id;

        // FIXME: "PHP message: PHP Deprecated:  Automatically populating $HTTP_RAW_POST_DATA is deprecated and will be removed in a future version.
        // To avoid this warning set 'always_populate_raw_post_data' to '-1' in php.ini and use the php://input stream instead. in Unknown on line 0"
        $json = file_get_contents('php://input');
        $payload = !empty($json) ? json_decode($json, true) : [];

        return $this->initShopContext($payload, $defaultShopId);
    }

    /**
     * @param string $header
     *
     * @return mixed|null
     */
    protected function getRequestHeader($header)
    {
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));

        if (array_key_exists($headerKey, $_SERVER)) {
            return $_SERVER[$headerKey];
        }

        return null;
    }

    /**
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
     * @param array $payload
     * @param int $defaultShopId
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function initShopContext(array $payload, $defaultShopId)
    {
        if (!isset($payload['shop_id'])) {
            // context fallback
            $payload['shop_id'] = $defaultShopId;
        }
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
     * @return bool
     */
    protected function displayMaintenancePage()
    {
        return false;
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

    /**
     * @param \Throwable|\Exception $e
     *
     * @return void
     *
     * @throws \PrestaShopException
     */
    protected function handleError($e)
    {
        SentryService::capture($e);

        $this->dieWithResponseJson([
            'error' => true,
            'message' => 'Failed processing your request',
        ], 500);
    }

    /**
     * @return void
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

        Logger::getInstance()->info('bearer: ' . $jwtString);

        try {
            $this->token = $this->oauth2Provider->validateToken($jwtString, $this->getScope(), $this->getAudience());
        } catch (SignatureInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        } catch (AudienceInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        } catch (ScopeInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        } catch (TokenExpiredException $e) {
            throw new UnauthorizedException($e->getMessage());
        } catch (TokenInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        }
    }

    /**
     * @param array $scope
     *
     * @return void
     *
     * @throws UnauthorizedException
     */
    protected function assertScope(array $scope)
    {
        try {
            $this->oauth2Provider->validateScope($this->token, $scope);
        } catch (ScopeInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        }
    }

    /**
     * @param array $audience
     *
     * @return void
     *
     * @throw UnauthorizedException
     */
    protected function assertAudience(array $audience)
    {
        try {
            $this->oauth2Provider->validateAudience($this->token, $audience);
        } catch (AudienceInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        }
    }
}

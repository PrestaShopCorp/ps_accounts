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
use PrestaShop\Module\PsAccounts\Polyfill\Traits\Controller\AjaxRender;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\SentryService;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Signer\Hmac\Sha256;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Signer\Key;
use ReflectionException;
use ReflectionParameter;

abstract class AbstractRestController extends ModuleFrontController
{
    use AjaxRender;

    const TOKEN_HEADER = 'X-PrestaShop-Signature';

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

    public function __construct()
    {
        parent::__construct();

        $this->ajax = true;
        $this->content_only = true;
        $this->controller_type = 'module';
    }

    /**
     * @return void
     */
    public function initContent()
    {
    }

    /**
     * @return void
     *
     * @throws \PrestaShopException
     */
    // public function init()
    // public function displayAjax()
    public function postProcess()
    {
        try {
            $payload = $this->extractPayload();
            $method = $_SERVER['REQUEST_METHOD'];
            // detect method from payload (hack with some shop server configuration)
            if (isset($payload['method'])) {
                $method = $payload['method'];
                unset($payload['method']);
            }
            $this->dispatchVerb($method, $payload);
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
     * @return array
     */
    protected function extractPayload()
    {
        $defaultShopId = Context::getContext()->shop->id;
        if ($this->authenticated) {
            return $this->decodePayload($defaultShopId);
        }

        return $this->decodeRawPayload($defaultShopId);
    }

    /**
     * @param int $defaultShopId
     *
     * @return array
     */
    protected function decodeRawPayload($defaultShopId = null)
    {
        $payload = $_REQUEST;
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
     * @param int $defaultShopId
     *
     * @return array
     */
    protected function decodePayload($defaultShopId = null)
    {
        /** @var RsaKeysProvider $shopKeysService */
        $shopKeysService = $this->module->getService(RsaKeysProvider::class);

        $jwtString = $this->getRequestHeader(self::TOKEN_HEADER);

        if ($jwtString) {
            $jwt = (new Parser())->parse($jwtString);

            $shop = new \Shop((int) $jwt->claims()->get('shop_id', $defaultShopId));

            if ($shop->id) {
                $this->setContextShop($shop);
                $publicKey = $shopKeysService->getPublicKey();

                if (
                    !empty($publicKey) &&
                    is_string($publicKey) &&
                    true === $jwt->verify(new Sha256(), new Key((string) $publicKey))
                ) {
                    return $jwt->claims()->all();
                }
                $this->module->getLogger()->error('Failed to verify token: ' . $jwtString);
            }

            $this->module->getLogger()->error('Failed to decode payload: ' . $jwtString);
        }

        throw new UnauthorizedException();
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

    /**
     * @param \Throwable|\Exception $e
     *
     * @return void
     *
     * @throws \PrestaShopException
     */
    private function handleError($e)
    {
        SentryService::capture($e);

        $this->dieWithResponseJson([
            'error' => true,
            'message' => 'Failed processing your request',
        ], 500);
    }
}

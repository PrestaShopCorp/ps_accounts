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

namespace PrestaShop\Module\PsAccounts\Controller;

use Context;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use PrestaShop\Module\PsAccounts\Exception\Http\HttpException;
use PrestaShop\Module\PsAccounts\Exception\Http\UnauthorizedException;
use PrestaShop\Module\PsAccounts\Handler\Error\Sentry;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

abstract class AbstractRestController extends \ModuleFrontController implements RestControllerInterface
{
    const METHOD_INDEX = 'index';
    const METHOD_SHOW = 'show';
    const METHOD_UPDATE = 'update';
    const METHOD_DELETE = 'delete';
    const METHOD_STORE = 'store';

    const PAYLOAD_PARAM = 'data';
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
     * @param mixed $id
     *
     * @return mixed
     */
    public function bindResource($id)
    {
        return $id;
    }

    /**
     * @return void
     *
     * @throws \Throwable
     */
    public function postProcess()
    {
        try {
            $payload = $this->decodePayload();
            $this->dispatchVerb(
                isset($payload['method']) && null !== $payload['method'] ? $payload['method'] : $_SERVER['REQUEST_METHOD'],
                $payload
            );
        } catch (HttpException $e) {
            $this->dieWithResponseJson([
                'error' => true,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            Sentry::capture($e);

            //$this->module->getLogger()->error($e);

            $this->dieWithResponseJson([
                'error' => true,
                'message' => 'Failed processing your request',
            ], 500);
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
        if (is_integer($httpResponseCode)) {
            http_response_code($httpResponseCode);
        }

        header('Content-Type: text/json');

        $this->ajaxDie(json_encode($response));
    }

    /**
     * @param array $payload
     *
     * @return array
     *
     * @throws \Exception
     */
    public function index(array $payload)
    {
        throw new \Exception('Method not implemented : ' . __METHOD__);
    }

    /**
     * @param mixed $id
     * @param array $payload
     *
     * @return array
     *
     * @throws \Exception
     */
    public function show($id, array $payload)
    {
        throw new \Exception('Method not implemented : ' . __METHOD__);
    }

    /**
     * @param array $payload
     *
     * @return array
     *
     * @throws \Exception
     */
    public function store(array $payload)
    {
        throw new \Exception('Method not implemented : ' . __METHOD__);
    }

    /**
     * @param mixed $id
     * @param array $payload
     *
     * @return array
     *
     * @throws \Exception
     */
    public function update($id, array $payload)
    {
        throw new \Exception('Method not implemented : ' . __METHOD__);
    }

    /**
     * @param mixed $id
     * @param array $payload
     *
     * @return array
     *
     * @throws \Exception
     */
    public function delete($id, array $payload)
    {
        throw new \Exception('Method not implemented : ' . __METHOD__);
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
        $id = null;
        if (array_key_exists($this->resourceId, $payload)) {
            $id = $this->bindResource($payload[$this->resourceId]);
        }

        $content = null;
        $statusCode = 200;

        switch ($httpMethod) {
            case 'GET':
                if (null !== $id) {
                    $content = $this->{self::METHOD_SHOW}($id, $payload);
                } else {
                    $content = $this->{self::METHOD_INDEX}($payload);
                }
                break;
            case 'POST':
                if (null !== $id) {
                    $content = $this->{self::METHOD_UPDATE}($id, $payload);
                } else {
                    $statusCode = 201;
                    $content = $this->{self::METHOD_STORE}($payload);
                }
                break;
            case 'PUT':
            case 'PATCH':
                $content = $this->{self::METHOD_UPDATE}($id, $payload);
                break;
            case 'DELETE':
                $statusCode = 204;
                $content = $this->{self::METHOD_DELETE}($id, $payload);
                break;
            default:
                throw new \Exception('Invalid Method : ' . $httpMethod);
        }

        $this->dieWithResponseJson($content, $statusCode);
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    protected function decodePayload()
    {
        /** @var RsaKeysProvider $shopKeysService */
        $shopKeysService = $this->module->getService(RsaKeysProvider::class);

        $jwtString = $this->getRequestHeader(self::TOKEN_HEADER);

        if ($jwtString) {
            $jwt = (new Parser())->parse($jwtString);

            $shop = new \Shop((int) $jwt->claims()->get('shop_id'));

            if ($shop->id) {
                $this->setContextShop($shop);
                $publicKey = $shopKeysService->getPublicKey();

                if (
                    null !== $publicKey &&
                    false !== $publicKey &&
                    '' !== $publicKey &&
                    true === $jwt->verify(new Sha256(), new Key((string) $publicKey))
                ) {
                    return $jwt->claims()->all();
                }
            }

            $this->module->getLogger()->info('Failed to verify token');
        }

        throw new UnauthorizedException();
    }

    /**
     * @param string $header
     *
     * @return mixed|null
     */
    public function getRequestHeader($header)
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
}

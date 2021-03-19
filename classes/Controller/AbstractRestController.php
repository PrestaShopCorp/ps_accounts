<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;
use PrestaShop\Module\PsAccounts\Exception\Http\HttpException;
use PrestaShop\Module\PsAccounts\Exception\Http\UnauthorizedException;
use PrestaShop\Module\PsAccounts\Service\ShopKeysService;

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
            $this->dispatchRestAction(
                $_SERVER['REQUEST_METHOD'],
                $this->decodePayload()
            );
        } catch (HttpException $e) {
            $this->dieWithResponseJson([
                'error' => true,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            $this->module->getLogger()->error($e);
            //Sentry::captureAndRethrow($e);
            $this->dieWithResponseJson([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param array $response
     *
     * @throws \PrestaShopException
     */
    public function dieWithResponseJson(array $response, $httpResponseCode=null)
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
    protected function dispatchRestAction($httpMethod, array $payload)
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
                    $content =  $this->{self::METHOD_UPDATE}($id, $payload);
                } else {
                    $statusCode = 201;
                    $content =  $this->{self::METHOD_STORE}($payload);
                }
                break;
            case 'PUT':
            case 'PATCH':
                $content = $this->{self::METHOD_UPDATE}($id, $payload);
                break;
            case 'DELETE':
                $statusCode = 204;
                $content =  $this->{self::METHOD_DELETE}($id, $payload);
                break;
            default:
                throw new \Exception('Invalid Method : ' . $httpMethod);
        }

        $this->dieWithResponseJson($content, $statusCode);
    }

    /**
     * @return array
     */
    protected function decodePayload()
    {
        /** @var ShopKeysService $shopKeysService */
        $shopKeysService = $this->module->getService(ShopKeysService::class);

        $jwtString = $this->getRequestHeader(self::TOKEN_HEADER);

        $this->module->getLogger()->info(self::TOKEN_HEADER . ' : ' . $jwtString);

        if ($jwtString) {

            $jwt = (new Parser())->parse($jwtString);

            if (true === $jwt->verify(new Sha256(), $shopKeysService->getPublicKey())) {
                return $jwt->claims()->all();
            }

            $this->module->getLogger()->info('Failed to verify token');
        }

        throw new UnauthorizedException();
    }

    /**
     * @param $header
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
}

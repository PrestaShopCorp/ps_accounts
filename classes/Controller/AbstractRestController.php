<?php

namespace PrestaShop\Module\PsAccounts\Controller;

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

    const PAYLOAD_PARAM = 'payload';

    /**
     * @var string
     */
    public $resourceId = 'id';

    /**
     * @return void
     *
     * @throws \Throwable
     */
    public function postProcess()
    {
        try {
            $this->dieWithResponseJson(
                $this->dispatchRestAction(
                    $_SERVER['REQUEST_METHOD'],
                    $this->decodePayload()
                )
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
     * @return array
     *
     * @throws \Exception
     */
    protected function dispatchRestAction($httpMethod, array $payload)
    {
        $id = null;
        if (array_key_exists($this->resourceId, $payload)) {
            $id = $payload[$this->resourceId];
        }

        switch ($httpMethod) {
            case 'GET':
                if (null !== $id) {
                    return $this->{self::METHOD_SHOW}($id, $payload);
                }
                return $this->{self::METHOD_INDEX}($payload);
            case 'POST':
                if (null !== $id) {
                    return $this->{self::METHOD_UPDATE}($id, $payload);
                }
                return $this->{self::METHOD_STORE}($payload);
            case 'PUT':
            case 'PATCH':
                return $this->{self::METHOD_UPDATE}($id, $payload);
            case 'DELETE':
                return $this->{self::METHOD_DELETE}($id, $payload);
        }
        throw new \Exception('Invalid Method : ' . $httpMethod);
    }

    /**
     * @return array
     */
    protected function decodePayload()
    {
        /** @var ShopKeysService $shopKeysService */
        $shopKeysService = $this->module->getService(ShopKeysService::class);

        try {
            // FIXME : for testing purpose
            //$_REQUEST[self::PAYLOAD_PARAM] = base64_encode($shopKeysService->encrypt(json_encode($_REQUEST)));

            //$this->module->getLogger()->info('Encrypted payload : [' . $_REQUEST[self::PAYLOAD_PARAM] . ']');

            $payload = json_decode($shopKeysService->decrypt(base64_decode($_REQUEST[self::PAYLOAD_PARAM])), true);

            //$this->module->getLogger()->info('Decrypted payload : [' . print_r($payload, true) . ']');

        } catch (\Exception $e) {

            $this->module->getLogger()->error($e);

            throw new UnauthorizedException();
        }

        return $payload;
    }
}

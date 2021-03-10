<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use PrestaShop\Module\PsAccounts\Handler\Error\Sentry;

abstract class AbstractRestController extends \ModuleFrontController implements RestControllerInterface
{
    // ?encrypt(query_string)
    // GET  apiShopUrl?shopId=
    // POST apiHmac
    // POST apiLinkAccount (update JWT, RefreshToken, ShopUuid, Email, EmailVerified)

    const RESOURCE_ID = 'id';

    const METHOD_INDEX = 'index';
    const METHOD_SHOW = 'show';
    const METHOD_UPDATE = 'update';
    const METHOD_DELETE = 'delete';
    const METHOD_STORE = 'store';

    /**
     * @return void
     *
     * @throws \Throwable
     */
    public function postProcess()
    {
        $payload = $this->decodePayload();

        $action = $this->getRestAction($_SERVER['REQUEST_METHOD'], $payload);

        try {
            $this->dieWithResponseJson($this->$action($payload));
        } catch (\Exception $e) {
            //Sentry::captureAndRethrow($e);
            $this->dieWithResponseJson([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param array $response
     *
     * @throws \PrestaShopException
     */
    public function dieWithResponseJson(array $response)
    {
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
     *
     * @return string
     */
    protected function getRestAction($httpMethod, $payload)
    {
        switch ($httpMethod) {
            case 'GET':
                if (isset($payload['id'])) {
                    return self::METHOD_SHOW;
                }
                return self::METHOD_INDEX;
            case 'POST':
                return self::METHOD_STORE;
            case 'PUT':
            case 'PATCH':
                return self::METHOD_UPDATE;
            case 'DELETE':
                return self::METHOD_DELETE;
        }
    }

    /**
     * @return mixed
     */
    protected function decodePayload()
    {
        return $_REQUEST;
    }
}

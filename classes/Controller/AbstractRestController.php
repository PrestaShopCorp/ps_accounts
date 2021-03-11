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
        try {
            $this->dieWithResponseJson(
                $this->dispatchRestAction(
                    $_SERVER['REQUEST_METHOD'],
                    $this->decodePayload()
                )
            );
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
     * @param array $payload
     *
     * @return void
     */
    protected function dispatchRestAction($httpMethod, array $payload)
    {
        $id = null;
        if (array_key_exists(self::RESOURCE_ID, $payload)) {
            $id = $payload[self::RESOURCE_ID];
        }

        switch ($httpMethod) {
            case 'GET':
                if (null !== $id) {
                    return $this->{self::METHOD_SHOW}($id, $payload);
                }
                return $this->{self::METHOD_INDEX}($payload);
            case 'POST':
                return $this->{self::METHOD_STORE}($payload);
            case 'PUT':
            case 'PATCH':
                return  $this->{self::METHOD_UPDATE}($id, $payload);
            case 'DELETE':
                return $this->{self::METHOD_DELETE}($id, $payload);
        }
    }

    /**
     * @return array
     */
    protected function decodePayload()
    {
//        $encrypted = base64_decode($_REQUEST['token']);
//        $json = decrypt($privKey, $e,crypted);
//        return json_decode($json);
        return $_REQUEST;
    }
}

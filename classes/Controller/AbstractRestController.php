<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use PrestaShop\Module\PsAccounts\Service\ShopKeysService;

abstract class AbstractRestController extends \ModuleFrontController implements RestControllerInterface
{
    const METHOD_INDEX = 'index';
    const METHOD_SHOW = 'show';
    const METHOD_UPDATE = 'update';
    const METHOD_DELETE = 'delete';
    const METHOD_STORE = 'store';

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

        // FIXME : for testing purpose
        $_REQUEST['token'] = base64_encode($shopKeysService->encrypt(json_encode($_REQUEST)));

        $this->module->getLogger()->info('Encrypted payload : [' . $_REQUEST['token'] . ']');

        $payload = json_decode($shopKeysService->decrypt(base64_decode($_REQUEST['token'])), true);

        $this->module->getLogger()->info('Decrypted payload : [' . print_r($payload, true) . ']');

        return $payload;
    }
}

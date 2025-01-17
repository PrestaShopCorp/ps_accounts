<?php

namespace PrestaShop\Module\PsAccounts\Tests;

use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Log\Logger;

class HttpTestClient extends Client
{
    /**
     * @var bool
     */
    private $fixModuleRoutes;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    public function __construct(array $config = [], $fixModuleRoutes = false)
    {
        $this->fixModuleRoutes = (bool) $fixModuleRoutes;
        $this->logger = Logger::getInstance();

        parent::__construct($config);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return Response
     */
    public function get($uri, array $options = [])
    {
        $this->parameterizeModuleRoute($uri, $options);

        return parent::get($uri, $options);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return Response
     */
    public function post($uri, array $options = [])
    {
        $this->parameterizeModuleRoute($uri, $options);

        return parent::post($uri, $options);
    }


    /**
     * @param string $uri
     * @param array $options
     *
     * @return Response
     */
    public function patch($uri, array $options = [])
    {
        $this->parameterizeModuleRoute($uri, $options);

        return parent::patch($uri, $options);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return Response
     */
    public function delete($uri, array $options = [])
    {
        $this->parameterizeModuleRoute($uri, $options);

        return parent::delete($uri, $options);
    }

    /**
     * @param string $route
     * @param array $options
     *
     * @return void
     */
    public function parameterizeModuleRoute(&$route, array &$options)
    {
        if ($this->fixModuleRoutes && preg_match(
                '/^.*\/(module)\/(ps_accounts)\/([a-zA-Z0-9]+)$/', $route, $matches
            )) {
            $query = isset($options['query']) ? $options['query'] : [];
            $query = array_merge($query, [
                'fc' => $matches[1],
                'module' => $matches[2],
                'controller' => $matches[3],
            ]);
            $route = '/index.php?' . http_build_query($query);
        }
    }
}

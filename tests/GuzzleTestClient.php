<?php

namespace PrestaShop\Module\PsAccounts\Tests;

use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts800\Vendor\GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class GuzzleTestClient extends Client
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
     * @param string|UriInterface $uri
     * @param array $options
     *
     * @return ResponseInterface
     */
    public function get($uri, array $options = [])
    {
        $this->parameterizeModuleRoute($uri, $options);

        return parent::get($uri, $options);
    }

    /**
     * @param string|UriInterface $uri
     * @param array $options
     *
     * @return ResponseInterface
     */
    public function post($uri, array $options = [])
    {
        $this->parameterizeModuleRoute($uri, $options);

        return parent::post($uri, $options);
    }


    /**
     * @param string|UriInterface $uri
     * @param array $options
     *
     * @return ResponseInterface
     */
    public function patch($uri, array $options = [])
    {
        $this->parameterizeModuleRoute($uri, $options);

        return parent::patch($uri, $options);
    }

    /**
     * @param string|UriInterface $uri
     * @param array $options
     *
     * @return ResponseInterface
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
            $route = '/index.php';
            $options['query'] = isset($options['query']) ? $options['query'] : [];
            $options['query'] = array_merge($options['query'], [
                'fc' => $matches[1],
                'module' => $matches[2],
                'controller' => $matches[3],
            ]);
        }
    }
}

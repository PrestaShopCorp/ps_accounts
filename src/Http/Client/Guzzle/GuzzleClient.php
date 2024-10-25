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

namespace PrestaShop\Module\PsAccounts\Http\Client\Guzzle;

use PrestaShop\Module\PsAccounts\Factory\CircuitBreakerFactory;
use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker\CircuitBreaker;
use PrestaShop\Module\PsAccounts\Http\Client\ClientInterface;
use PrestaShop\Module\PsAccounts\Vendor\GuzzleHttp\Client;

abstract class GuzzleClient implements ClientInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $route;

    /**
     * @var int
     */
    protected $timeout = 10;

    /**
     * @var bool
     */
    protected $catchExceptions = false;

    /**
     * @var CircuitBreaker
     */
    protected $circuitBreaker;

    /**
     * @param array $options
     *
     * @throws \Exception
     */
    public function __construct($options)
    {
        $this->circuitBreaker = CircuitBreakerFactory::create(
            isset($options['name']) ? $options['name'] : static::class
        );
        unset($options['name']);
    }

    /**
     * @param mixed $response
     *
     * @return array
     */
    public function handleResponse($response)
    {
        $responseContents = $this->getResponseJson($response);

        return [
            'status' => $this->responseIsSuccessful($responseContents, $response->getStatusCode()),
            'httpCode' => $response->getStatusCode(),
            'body' => $responseContents,
        ];
    }

    /**
     * @param mixed $response
     *
     * @return mixed
     */
    public function getResponseJson($response)
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param array $options payload
     *
     * @return array return response or false if no response
     */
    public function post(array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($options) {
            $response = $this->getClient()->post($this->getRoute(), $options);
            $response = $this->handleResponse($response);
            $this->logResponseError($response, $options);

            return $response;
        });
    }

    /**
     * @param array $options payload
     *
     * @return array return response or false if no response
     */
    public function patch(array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($options) {
            $response = $this->getClient()->patch($this->getRoute(), $options);
            $response = $this->handleResponse($response);
            $this->logResponseError($response, $options);

            return $response;
        });
    }

    /**
     * @param array $options payload
     *
     * @return array return response or false if no response
     */
    public function get(array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($options) {
            $response = $this->getClient()->get($this->getRoute(), $options);
            $response = $this->handleResponse($response);
            $this->logResponseError($response, $options);

            return $response;
        });
    }

    /**
     * @param array $options payload
     *
     * @return array return response array
     */
    public function delete(array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($options) {
            $response = $this->getClient()->delete($this->getRoute(), $options);
            $response = $this->handleResponse($response);
            $this->logResponseError($response, $options);

            return $response;
        });
    }

    /**
     * @param array $responseContents
     * @param int $httpStatusCode
     *
     * @return bool
     */
    public function responseIsSuccessful($responseContents, $httpStatusCode)
    {
        return '2' === substr((string) $httpStatusCode, 0, 1);
    }

    /**
     * Getter for client.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     *
     * @return void
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param string $route
     *
     * @return void
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @param array $response
     * @param array $options
     *
     * @return void
     */
    private function logResponseError(array $response, array $options)
    {
        // If response is not successful only
        if (!$response['status']) {
            /** @var \Ps_accounts $module */
            $module = \Module::getInstanceByName('ps_accounts');
            try {
                $logger = $module->getLogger();
                $logger->error('route ' . $this->getRoute());
                $logger->error('options ' . var_export($options, true));
                $logger->error('response ' . var_export($response, true));
            } catch (\Exception $e) {
            }
        }
    }
}

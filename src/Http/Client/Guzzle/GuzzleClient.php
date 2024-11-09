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
use PrestaShop\Module\PsAccounts\Log\Logger;

class GuzzleClient
{
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

//        \Tools::refreshCACertFile();

        // TODO: http_errors
        // TODO: circuit breaker
//        $this->client = new Client(array_merge(
//            [
//                'timeout' => $this->timeout,
//                'http_errors' => $this->catchExceptions,
//                'verify' => $this->getVerify(),
//            ],
//            $options
//        ));
    }

    /**
     * @param array $options payload
     *
     * @return array return response or false if no response
     */
    public function post(array $options = [])
    {
//        return $this->circuitBreaker->call(function () use ($options) {
//            $response = $this->getClient()->post($this->getRoute(), $options);
//            $response = $this->handleResponse($response);
//            $this->logResponseError($response, $options);
//
//            return $response;
//        });

        $ch = $this->initCurl($options);

        curl_setopt($ch, CURLOPT_POST, true);

        $this->initPayloadJson($options, $ch);

        $response = $this->getResponse($ch, curl_exec($ch));

        curl_close($ch);

        return $response;
    }

    /**
     * @param array $options payload
     *
     * @return array return response or false if no response
     */
    public function patch(array $options = [])
    {
//        return $this->circuitBreaker->call(function () use ($options) {
//            $response = $this->getClient()->patch($this->getRoute(), $options);
//            $response = $this->handleResponse($response);
//            $this->logResponseError($response, $options);
//
//            return $response;
//        });

        $ch = $this->initCurl($options);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');

        $this->initPayloadJson($options, $ch);

        $response = $this->getResponse($ch, curl_exec($ch));

        curl_close($ch);

        return $response;
    }

    /**
     * @param array $options payload
     *
     * @return array return response or false if no response
     */
    public function get(array $options = [])
    {
//        return $this->circuitBreaker->call(function () use ($options) {
//            $response = $this->getClient()->get($this->getRoute(), $options);
//            $response = $this->handleResponse($response);
//            $this->logResponseError($response, $options);
//
//            return $response;
//        });
        $ch = $this->initCurl($options);

        $response = $this->getResponse($ch, curl_exec($ch));

        curl_close($ch);

        return $response;
    }

    /**
     * @param array $options payload
     *
     * @return array return response array
     */
    public function delete(array $options = [])
    {
//        return $this->circuitBreaker->call(function () use ($options) {
//            $response = $this->getClient()->delete($this->getRoute(), $options);
//            $response = $this->handleResponse($response);
//            $this->logResponseError($response, $options);
//
//            return $response;
//        });

        $ch = $this->initCurl($options);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $response = $this->getResponse($ch, curl_exec($ch));

        curl_close($ch);

        return $response;
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
     * @param resource $ch
     * @param string $response
     *
     * @return array
     */
    public function getResponse($ch, $response)
    {
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $res =  [
            'status' => $this->responseIsSuccessful([], $statusCode),
            'httpCode' => $statusCode,
            'body' => \json_decode($response, true),
        ];
        $this->logResponse($response, $ch);

        return $res;
    }

    /**
     * @param array $response
     * @param mixed $chr
     *
     * @return void
     */
    private function logResponse(array $response, array $chr)
    {
        // If response is not successful only
        if (!$response['status']) {
            try {
                $logger = Logger::getInstance();
                $logger->error('route ' . $this->getRoute());
                $logger->error('options ' . var_export(curl_getinfo($chr), true));
                $logger->error('response ' . var_export($response, true));
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * @param array $options
     * @param resource $ch
     *
     * @return void
     */
    public function initHeaders(array $options, $ch)
    {
        if (array_key_exists('headers', $options)) {
            $headers = [];
            foreach ($options['headers'] as $header => $value) {
                $headers[] = "$header: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    }

    /**
     * @return resource
     */
    public function initRoute()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');
        $apiRoute = $module->getParameter('ps_accounts.accounts_api_url');
        $absRoute = preg_replace('/\/$/', '', $apiRoute) . preg_replace('/\/+/', '/', '/' . $this->getRoute());

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $absRoute);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);

        return $ch;
    }

    /**
     * @param resource $ch
     *
     * @return void
     */
    public function initSsl($ch)
    {
        $checkSsl = $this->getVerify();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $checkSsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $checkSsl);
    }

    /**
     * @param array $options
     * @param resource $ch
     *
     * @return void
     */
    public function initPayloadJson(array $options, $ch)
    {
        if (array_key_exists('json', $options)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['json']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        }
    }

    /**
     * @return bool
     */
    protected function getVerify()
    {
        if (version_compare((string) phpversion(), '7', '>=')) {
            /** @var \Ps_accounts $module */
            $module = \Module::getInstanceByName('ps_accounts');

            return (bool) $module->getParameter('ps_accounts.check_api_ssl_cert');
        }
        // bypass certificate expiration issue with PHP5.6
        return false;

//        if ((bool) $module->getParameter('ps_accounts.check_api_ssl_cert')) {
//            if (defined('_PS_CACHE_CA_CERT_FILE_') && file_exists(constant('_PS_CACHE_CA_CERT_FILE_'))) {
//                return constant('_PS_CACHE_CA_CERT_FILE_');
//            }
//
//            return true;
//        }
//        return false;
    }

    /**
     * @param array $options
     *
     * @return resource
     */
    public function initCurl(array $options)
    {
        $ch = $this->initRoute();
        $this->initHeaders($options, $ch);
        $this->initSsl($ch);
        return $ch;
    }
}

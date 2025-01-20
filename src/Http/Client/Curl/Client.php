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

namespace PrestaShop\Module\PsAccounts\Http\Client\Curl;

use PrestaShop\Module\PsAccounts\Http\Client\CircuitBreaker;
use PrestaShop\Module\PsAccounts\Http\Client\Options;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Log\Logger;

class Client
{
    /**
     * @var string
     */
    protected $baseUri;

    /**
     * @var string
     */
    protected $route;

    /**
     * @var string
     */
    protected $userAgent = 'ps_accounts/' . \Ps_accounts::VERSION;

    /**
     * @var int
     */
    protected $timeout = 10;

    /**
     * @var CircuitBreaker\CircuitBreaker
     */
    protected $circuitBreaker;

    /**
     * @var bool
     */
    protected $objectResponse = false;

    /**
     * @var bool
     */
    protected $sslCheck = true;

    /**
     * @var bool
     */
    protected $allowRedirects;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @param array $options
     *
     * @throws \Exception
     */
    public function __construct($options)
    {
        $this->circuitBreaker = CircuitBreaker\Factory::create(
            isset($options['name']) ? $options['name'] : static::class
        );

        if ($this->objectResponse) {
            $this->circuitBreaker->setDefaultFallbackResponse(
                new Response($this->circuitBreaker->getDefaultFallbackResponse())
            );
        }

        unset($options['name']);
//        \Tools::refreshCACertFile();

        // FIXME headers
        foreach (['baseUri', 'timeout', 'objectResponse', 'sslCheck', 'allowRedirects', 'headers'] as $option) {
            if (isset($options[$option])) {
                $this->$option = $options[$option];
            }
        }
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response|array return response or false if no response
     */
    public function post($route, array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($route, $options) {
            $this->setRoute($route);

            $ch = $this->initCurl($options);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

            $this->initPayload($options, $ch);

            $response = $this->getResponse($ch, curl_exec($ch));

            curl_close($ch);

            return $response;
        });
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response|array return response or false if no response
     */
    public function patch($route, array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($route, $options) {
            $this->setRoute($route);

            $ch = $this->initCurl($options);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');

            $this->initPayload($options, $ch);

            $response = $this->getResponse($ch, curl_exec($ch));

            curl_close($ch);

            return $response;
        });
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response|array return response or false if no response
     */
    public function get($route, array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($route, $options) {
            $this->setRoute($route);

            $ch = $this->initCurl($options);

            $response = $this->getResponse($ch, curl_exec($ch));

            curl_close($ch);

            return $response;
        });
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response|array return response array
     */
    public function delete($route, array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($route, $options) {
            $this->setRoute($route);

            $ch = $this->initCurl($options);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

            $response = $this->getResponse($ch, curl_exec($ch));

            curl_close($ch);

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
     * @param mixed $ch
     * @param mixed $response
     *
     * @return Response|array
     *
     * @throws CircuitBreaker\CircuitBreakerException
     */
    public function getResponse($ch, $response)
    {
        switch (curl_errno($ch)) {
            case CURLE_OPERATION_TIMEDOUT:
            case CURLE_COULDNT_CONNECT:
                throw new CircuitBreaker\CircuitBreakerException('Curl error: ' . curl_error($ch));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $res = [
            'status' => $this->responseIsSuccessful([], $statusCode),
            'httpCode' => $statusCode,
            'body' => is_array($response) ?
                $response :
                \json_decode($response, true),
        ];
        $this->logResponse($res, $ch);

        if ($this->objectResponse) {
            return new Response($res);
        }

        return $res;
    }

    /**
     * @param array $response
     * @param mixed $ch
     *
     * @return void
     */
    private function logResponse($response, $ch)
    {
        if (!$response['status']) {
            Logger::getInstance()->error('response ' . var_export($response, true));
        } else {
            Logger::getInstance()->info('response ' . var_export($response, true));
        }
    }

    /**
     * @param array $options
     * @param mixed $ch
     *
     * @return void
     */
    public function initHeaders(array $options, $ch)
    {
        $assoc = $this->headers;
        if (array_key_exists(Options::REQ_JSON, $options)) {
            $assoc['Content-Type'] = 'application/json';
        }
        if (array_key_exists(Options::REQ_HEADERS, $options)) {
            $assoc = array_merge($assoc, $options[Options::REQ_HEADERS]);
        }

        $headers = [];
        foreach ($assoc as $header => $value) {
            $headers[] = "$header: $value";
        }

        Logger::getInstance()->info('headers ' . var_export($headers, true));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * @return mixed
     */
    public function initRoute()
    {
        $absRoute = $this->getRoute();
        if (!empty($this->baseUri) && !preg_match('/^http(s)?:\/\//', $absRoute)) {
            $absRoute = preg_replace('/\/$/', '', $this->baseUri) . preg_replace('/\/+/', '/', '/' . $absRoute);
        }

        if (empty($absRoute)) {
            throw new \InvalidArgumentException('Route must be set before initRoute()');
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $absRoute);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->allowRedirects);
        curl_setopt($ch, CURLOPT_POSTREDIR, $this->allowRedirects ? 3 : 0);

        curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgent());
        //curl_setopt($ch, CURLOPT_VERBOSE, true);

        return $ch;
    }

    /**
     * @param mixed $ch
     *
     * @return void
     */
    public function initSsl($ch)
    {
        $checkSsl = $this->getVerify();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $checkSsl ? 2 : 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $checkSsl);
    }

    /**
     * @param array $options
     * @param mixed $ch
     *
     * @return void
     */
    public function initPayload(array $options, $ch)
    {
        if (array_key_exists(Options::REQ_JSON, $options)) {
            Logger::getInstance()->info('payload ' . var_export($options[Options::REQ_JSON], true));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options[Options::REQ_JSON]) ?: '');
        } elseif (array_key_exists(Options::REQ_FORM, $options)) {
            Logger::getInstance()->info('payload ' . var_export($options[Options::REQ_FORM], true));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options[Options::REQ_FORM]));
        }
    }

    /**
     * @return bool
     */
    protected function getVerify()
    {
        if (version_compare((string) phpversion(), '7', '>=')) {
            return $this->sslCheck;
        }
        // bypass certificate expiration issue with PHP5.6
        return false;
    }

    /**
     * @param array $options
     *
     * @return mixed
     */
    public function initCurl(array $options)
    {
        $ch = $this->initRoute();
        $this->initHeaders($options, $ch);
        $this->initSsl($ch);

        Logger::getInstance()->info('options ' . var_export(curl_getinfo($ch), true));

        return $ch;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }
}

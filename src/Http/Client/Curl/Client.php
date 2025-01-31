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

        unset($options['name']);
//        \Tools::refreshCACertFile();

        foreach ([
                     'baseUri',
                     'userAgent',
                     'timeout',
                     'sslCheck',
                     'allowRedirects',
                     'headers',
                 ] as $option) {
            if (isset($options[$option])) {
                $this->$option = $options[$option];
            }
        }
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response return response or false if no response
     */
    public function post($route, array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($route, $options) {
            $ch = $this->initRequest($route, $options);

            $this->initPayload($options, $ch);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

            $response = $this->getResponse($ch);

            curl_close($ch);

            return $response;
        });
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response return response or false if no response
     */
    public function patch($route, array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($route, $options) {
            $ch = $this->initRequest($route, $options);

            $this->initPayload($options, $ch);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');

            $response = $this->getResponse($ch);

            curl_close($ch);

            return $response;
        });
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response return response or false if no response
     */
    public function get($route, array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($route, $options) {
            $ch = $this->initRequest($route, $options);

            $response = $this->getResponse($ch);

            curl_close($ch);

            return $response;
        });
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response return response array
     */
    public function delete($route, array $options = [])
    {
        return $this->circuitBreaker->call(function () use ($route, $options) {
            $ch = $this->initRequest($route, $options);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

            $response = $this->getResponse($ch);

            curl_close($ch);

            return $response;
        });
    }

    /**
     * @param mixed $ch
     *
     * @return Response
     *
     * @throws CircuitBreaker\CircuitBreakerException
     */
    public function getResponse($ch)
    {
        $res = curl_exec($ch);

        switch (curl_errno($ch)) {
            case CURLE_OPERATION_TIMEDOUT:
            case CURLE_COULDNT_CONNECT:
                throw new CircuitBreaker\CircuitBreakerException('Curl error: ' . curl_error($ch));
            default:
                // TODO: manage curl_errors
                // TODO: unbind CircuitBreaker
                // TODO: log properly error/request
                break;
        }

        $decodedBody = json_decode((string) $res, true);
        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $response = new Response(
            is_array($decodedBody) ? $decodedBody : [],
            $statusCode
        );
        $this->logResponse($response, $ch);

        return $response;
    }

    /**
     * @param Response $response
     * @param mixed $ch
     *
     * @return void
     */
    private function logResponse(Response $response, $ch)
    {
        if (!$response->isValid()) {
            Logger::getInstance()->error('response ' . var_export($response, true));
        } else {
            Logger::getInstance()->info('response ' . var_export($response, true));
        }
    }

    /**
     * @param mixed $ch
     * @param array $options
     *
     * @return void
     */
    public function initHeaders($ch, array $options)
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
     * @param mixed $ch
     * @param string $route
     *
     * @return void
     */
    public function initRoute($ch, $route)
    {
        $absRoute = $route;
        if (!empty($this->baseUri) && !preg_match('/^http(s)?:\/\//', $absRoute)) {
            $absRoute = preg_replace('/\/$/', '', $this->baseUri) . preg_replace('/\/+/', '/', '/' . $absRoute);
        }

        if (empty($absRoute)) {
            throw new \InvalidArgumentException('route must not be empty');
        }

        curl_setopt($ch, CURLOPT_URL, $absRoute);
    }

    /**
     * @param mixed $ch
     * @param int $timeout
     *
     * @return void
     */
    public function initTimeout($ch, $timeout)
    {
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * @param mixed $ch
     *
     * @return void
     */
    public function initSsl($ch)
    {
        $checkSsl = $this->getSslCheck();
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
        curl_setopt($ch, CURLOPT_POST, true);
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
    protected function getSslCheck()
    {
        // bypass certificate expiration issue with PHP5.6
        if (version_compare((string) phpversion(), '7', '>=')) {
            return $this->sslCheck;
        }

        return false;
    }

    /**
     * @param string $route
     * @param array $options
     *
     * @return mixed
     */
    protected function initRequest($route, array $options)
    {
        $ch = curl_init();

        $this->initRoute($ch, $route);
        $this->initHeaders($ch, $options);
        $this->initSsl($ch);
        $this->initTimeout($ch, $this->timeout);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->allowRedirects);
        curl_setopt($ch, CURLOPT_POSTREDIR, $this->allowRedirects ? 3 : 0);

        if (!empty($this->userAgent)) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        }
        //curl_setopt($ch, CURLOPT_VERBOSE, true);

        Logger::getInstance()->info('options ' . var_export(curl_getinfo($ch), true));

        return $ch;
    }
}

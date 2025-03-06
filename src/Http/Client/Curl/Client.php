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
use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Http\Client\Exception\ClientException;
use PrestaShop\Module\PsAccounts\Http\Client\Exception\ConnectException;
use PrestaShop\Module\PsAccounts\Http\Client\Exception\RequiredPropertyException;
use PrestaShop\Module\PsAccounts\Http\Client\Exception\UndefinedPropertyException;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Log\Logger;

class Client
{
    /**
     * @var ClientConfig
     */
    protected $config;

    /**
     * @var CircuitBreaker\CircuitBreaker
     */
    protected $circuitBreaker;

    /**
     * @param array $options
     *
     * @throws RequiredPropertyException
     * @throws UndefinedPropertyException
     */
    public function __construct($options)
    {
        $this->config = new ClientConfig(array_merge($options, [
            ClientConfig::USER_AGENT => 'ps_accounts/' . \Ps_accounts::VERSION,
        ]));

        $this->circuitBreaker = CircuitBreaker\Factory::create(
            !empty($this->config->name) ? $options['name'] : static::class
        );
    }

    /**
     * @return ClientConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response
     *
     * @throws RequiredPropertyException
     * @throws UndefinedPropertyException
     */
    public function post($route, array $options = [])
    {
        $ch = $this->initRequest(new Request(array_merge($options, [
            Request::URI => $route,
        ])));
        $this->initMethod($ch, 'POST');

        return $this->getSafeResponse($ch);
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response
     *
     * @throws RequiredPropertyException
     * @throws UndefinedPropertyException
     */
    public function patch($route, array $options = [])
    {
        $ch = $this->initRequest(new Request(array_merge($options, [
            Request::URI => $route,
        ])));
        $this->initMethod($ch, 'PATCH');

        return $this->getSafeResponse($ch);
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response
     *
     * @throws RequiredPropertyException
     * @throws UndefinedPropertyException
     */
    public function get($route, array $options = [])
    {
        $ch = $this->initRequest(new Request(array_merge($options, [
            Request::URI => $route,
        ])));

        return $this->getSafeResponse($ch);
    }

    /**
     * @param string $route
     * @param array $options payload
     *
     * @return Response
     *
     * @throws RequiredPropertyException
     * @throws UndefinedPropertyException
     */
    public function delete($route, array $options = [])
    {
        $ch = $this->initRequest(new Request(array_merge($options, [
            Request::URI => $route,
        ])));
        $this->initMethod($ch, 'DELETE');

        return $this->getSafeResponse($ch);
    }

    /**
     * @param mixed $ch
     *
     * @return Response
     *
     * @throws ClientException
     * @throws ConnectException
     */
    protected function getResponse($ch)
    {
        $res = curl_exec($ch);

        $this->handleError($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $response = new Response(
            (string) $res,
            $statusCode
        );

        curl_close($ch);

        return $response;
    }

    /**
     * @param mixed $ch
     *
     * @return Response
     */
    protected function getSafeResponse($ch)
    {
        /** @var Response $response */
        $response = $this->circuitBreaker->call(function () use ($ch) {
            return $this->getResponse($ch);
        });

        $this->logResponse($response);

        return $response;
    }

    /**
     * @param mixed $ch
     * @param Request $request
     *
     * @return void
     */
    protected function initHeaders($ch, Request $request)
    {
        $assoc = $this->config->headers;
        if (!empty($request->headers)) {
            $assoc = array_merge($assoc, $request->headers);
        }
        if (!empty($request->json)) {
            $assoc['Content-Type'] = 'application/json';
        }

        $headers = [];
        foreach ($assoc as $header => $value) {
            $headers[] = "$header: $value";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * @param mixed $ch
     * @param Request $request
     *
     * @return void
     */
    protected function initRoute($ch, Request $request)
    {
        $absRoute = $request->uri;
        if (!empty($this->config->baseUri) && !preg_match('/^http(s)?:\/\//', $absRoute)) {
            $absRoute = preg_replace('/\/$/', '', $this->config->baseUri) . preg_replace('/\/+/', '/', '/' . $absRoute);
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
    protected function initTimeout($ch, $timeout)
    {
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * @param mixed $ch
     *
     * @return void
     */
    protected function initSsl($ch)
    {
        $checkSsl = $this->getSslCheck();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $checkSsl ? 2 : 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $checkSsl);
    }

    /**
     * @param mixed $ch
     * @param Request $request
     *
     * @return void
     */
    protected function initPayload($ch, Request $request)
    {
        if (!empty($request->json)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request->json) ?: '');
        } elseif (!empty($request->form)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request->form) ?: '');
        }
    }

    /**
     * @param mixed $ch
     * @param string $method
     *
     * @return void
     */
    protected function initMethod($ch, $method)
    {
        if (empty($method)) {
            throw new \InvalidArgumentException('method must not be empty');
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }

    /**
     * @return bool
     */
    protected function getSslCheck()
    {
        // bypass certificate expiration issue with PHP5.6
        if (version_compare((string) phpversion(), '7', '>=')) {
            return $this->config->sslCheck;
        }

        return false;
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    protected function initRequest(Request $request)
    {
        $ch = curl_init();

        $this->initRoute($ch, $request);
        $this->initHeaders($ch, $request);
        $this->initSsl($ch);
        $this->initTimeout($ch, $this->config->timeout);
        $this->initPayload($ch, $request);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->config->allowRedirects);
        curl_setopt($ch, CURLOPT_POSTREDIR, $this->config->allowRedirects ? 3 : 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        if (!empty($this->config->userAgent)) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->config->userAgent);
        }
        //curl_setopt($ch, CURLOPT_VERBOSE, true);

        return $ch;
    }

    /**
     * @param mixed $ch
     *
     * @return void
     *
     * @throws ClientException
     * @throws ConnectException
     */
    protected function handleError($ch)
    {
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);

        $message = '- Request : ' . var_export(curl_getinfo($ch), true);

        if ($curlErrno) {
            Logger::getInstance()->error($message);

            curl_close($ch);

            switch ($curlErrno) {
                case CURLE_OPERATION_TIMEDOUT:
                case CURLE_COULDNT_CONNECT:
                    throw new ConnectException('Curl error: ' . $curlError, $curlErrno);
                default:
                    throw new ClientException('Curl error: ' . $curlError, $curlErrno);
            }
        } else {
            Logger::getInstance()->info($message);
        }
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    protected function logResponse(Response $response)
    {
        $message = '- Response : ' . var_export($response, true);

        if (!$response->isSuccessful) {
            Logger::getInstance()->error($message);
        } else {
            Logger::getInstance()->info($message);
        }
    }
}

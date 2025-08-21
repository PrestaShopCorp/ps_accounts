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
    public function get($route, array $options = [])
    {
        return $this->getSafeResponse($this->initRequest(new Request(array_merge($options, [
            Request::URI => $route,
        ]))));
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
        return $this->getSafeResponse($this->initRequest(new Request(array_merge($options, [
            Request::METHOD => 'POST',
            Request::URI => $route,
        ]))));
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
        return $this->getSafeResponse($this->initRequest(new Request(array_merge($options, [
            Request::METHOD => 'PATCH',
            Request::URI => $route,
        ]))));
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
    public function put($route, array $options = [])
    {
        return $this->getSafeResponse($this->initRequest(new Request(array_merge($options, [
            Request::METHOD => 'PUT',
            Request::URI => $route,
        ]))));
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
        return $this->getSafeResponse($this->initRequest(new Request(array_merge($options, [
            Request::METHOD => 'DELETE',
            Request::URI => $route,
        ]))));
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws ClientException
     * @throws ConnectException
     */
    protected function getResponse(Request $request)
    {
        $res = (string) curl_exec($request->handler);

        $this->handleError($request);

        // Get the size of the header from the cURL info
        $header_size = curl_getinfo($request->handler, CURLINFO_HEADER_SIZE);

        // Extract the headers from the response string
        $headers = $this->parseHeaders(substr($res, 0, $header_size));

        // Extract the body from the response string
        $body = substr($res, $header_size);

        $statusCode = curl_getinfo($request->handler, CURLINFO_RESPONSE_CODE);
        $response = new Response(
            (string) $body,
            $statusCode,
            $headers
        );
        $response->request = $request;

        curl_close($request->handler);

        return $response;
    }

    /**
     * @param string $headers
     *
     * @return array
     */
    protected function parseHeaders($headers)
    {
        // Parse the header string into an array of lines
        $headerLines = explode("\r\n", trim($headers));

        // Initialize an empty array for the parsed headers
        $parsedHeaders = [];

        foreach ($headerLines as $line) {
            // Skip the status line (e.g., HTTP/1.1 200 OK)
            if (strpos($line, ':') === false) {
                continue;
            }

            // Split the header line at the first colon
            list($key, $value) = explode(':', $line, 2);

            // Trim and store the key-value pair
            $parsedHeaders[trim($key)] = trim($value);
        }

        return $parsedHeaders;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    protected function getSafeResponse(Request $request)
    {
        /** @var Response $response */
        $response = $this->circuitBreaker->call(function () use ($request) {
            return $this->getResponse($request);
        });

        $this->logResponse($response);

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    protected function initHeaders(Request $request)
    {
        $defaults = [];
        if (!empty($request->json)) {
            $defaults['Content-Type'] = 'application/json';
        } elseif (!empty($request->form)) {
            $defaults['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        $headers = [];
        foreach (array_merge($defaults, $this->config->headers, $request->headers)
                 as $header => $value) {
            $headers[] = "$header: $value";
        }

        curl_setopt($request->handler, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    protected function initUri(Request $request)
    {
        $absUri = $request->uri;
        if (!empty($this->config->baseUri) && !preg_match('/^http(s)?:\/\//', $absUri)) {
            $absUri = preg_replace('/\/$/', '', $this->config->baseUri) . preg_replace('/\/+/', '/', '/' . $absUri);
        }

        if (empty($absUri)) {
            throw new \InvalidArgumentException('route must not be empty');
        }

        if (!empty($request->query)) {
            $sep = preg_match('/\?/', $absUri) ? '&' : '?';
            $absUri .= $sep . http_build_query($request->query);
        }
        $request->absUri = $absUri;

        curl_setopt($request->handler, CURLOPT_URL, $request->absUri);
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    protected function initSsl(Request $request)
    {
        $checkSsl = $this->getSslCheck();
        curl_setopt($request->handler, CURLOPT_SSL_VERIFYHOST, $checkSsl ? 2 : 0);
        curl_setopt($request->handler, CURLOPT_SSL_VERIFYPEER, $checkSsl);
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    protected function initPayload(Request $request)
    {
        if (!empty($request->json)) {
            curl_setopt($request->handler, CURLOPT_POST, true);
            curl_setopt($request->handler, CURLOPT_POSTFIELDS, json_encode($request->json) ?: '');
        } elseif (!empty($request->form)) {
            curl_setopt($request->handler, CURLOPT_POST, true);
            curl_setopt($request->handler, CURLOPT_POSTFIELDS, http_build_query($request->form) ?: '');
        }
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    protected function initMethod(Request $request)
    {
        if (!empty($request->method)) {
            curl_setopt($request->handler, CURLOPT_CUSTOMREQUEST, $request->method);
        }
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
     * @return Request
     */
    protected function initRequest(Request $request)
    {
        $request->handler = curl_init();

        $this->initUri($request);
        $this->initHeaders($request);
        $this->initSsl($request);
        $this->initPayload($request);
        $this->initMethod($request);

        curl_setopt($request->handler, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($request->handler, CURLOPT_TIMEOUT, $this->config->timeout);
        curl_setopt($request->handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request->handler, CURLOPT_FOLLOWLOCATION, $this->config->allowRedirects);
        curl_setopt($request->handler, CURLOPT_POSTREDIR, $this->config->allowRedirects ? 3 : 0);
        curl_setopt($request->handler, CURLINFO_HEADER_OUT, true);
        curl_setopt($request->handler, CURLOPT_HEADER, true);

        if (!empty($this->config->userAgent)) {
            curl_setopt($request->handler, CURLOPT_USERAGENT, $this->config->userAgent);
        }
        //curl_setopt($request->handler, CURLOPT_VERBOSE, true);

        return $request;
    }

    /**
     * @param Request $request
     *
     * @return void
     *
     * @throws ClientException
     * @throws ConnectException
     */
    protected function handleError(Request $request)
    {
        $curlErrno = curl_errno($request->handler);
        $curlError = curl_error($request->handler);

        if ($curlErrno) {
            $message = '- Request : ' . var_export(curl_getinfo($request->handler), true);

            Logger::getInstance()->error($message);

            curl_close($request->handler);

            switch ($curlErrno) {
                case CURLE_OPERATION_TIMEDOUT:
                case CURLE_COULDNT_CONNECT:
                    throw new ConnectException('Curl error: ' . $curlError, $curlErrno);
                default:
                    throw new ClientException('Curl error: ' . $curlError, $curlErrno);
            }
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

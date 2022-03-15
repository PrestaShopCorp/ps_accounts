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

namespace PrestaShop\Module\PsAccounts\Api\Client\Guzzle;

use GuzzleHttp\Client;
use PrestaShop\Module\PsAccounts\Api\Client\ClientInterface;

/**
 * Construct the client used to make call to differents api.
 */
abstract class AbstractGuzzleClient implements ClientInterface
{
    /**
     * Guzzle Client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Api route.
     *
     * @var string
     */
    protected $route;

    /**
     * Set how long guzzle will wait a response before end it up.
     *
     * @var int
     */
    protected $timeout = 10;

    /**
     * If set to false, you will not be able to catch the error
     * guzzle will show a different error message.
     *
     * @var bool
     */
    protected $catchExceptions = false;

    /**
     * Wrapper of method post from guzzle client.
     *
     * @param array $options payload
     *
     * @return array return response or false if no response
     */
    public function post(array $options = [])
    {
        $response = $this->getClient()->post($this->getRoute(), $options);
        $response = $this->handleResponse($response);
        // If response is not successful only
        if (\Configuration::get('PS_ACCOUNTS_DEBUG_LOGS_ENABLED') && !$response['status']) {
            /**
             * @var \Ps_accounts
             */
            $module = \Module::getInstanceByName('ps_accounts');
            $logger = $module->getLogger();
            $logger->debug('route ' . $this->getRoute());
            $logger->debug('options ' . var_export($options, true));
            $logger->debug('response ' . var_export($response, true));
        }

        return $response;
    }

    /**
     * Wrapper of method patch from guzzle client.
     *
     * @param array $options payload
     *
     * @return array return response or false if no response
     */
    public function patch(array $options = [])
    {
        $response = $this->getClient()->patch($this->getRoute(), $options);
        $response = $this->handleResponse($response);
        // If response is not successful only
        if (\Configuration::get('PS_ACCOUNTS_DEBUG_LOGS_ENABLED') && !$response['status']) {
            /**
             * @var \Ps_accounts
             */
            $module = \Module::getInstanceByName('ps_accounts');
            $logger = $module->getLogger();
            $logger->debug('route ' . $this->getRoute());
            $logger->debug('options ' . var_export($options, true));
            $logger->debug('response ' . var_export($response, true));
        }

        return $response;
    }

    /**
     * Wrapper of method post from guzzle client.
     *
     * @param array $options payload
     *
     * @return array return response or false if no response
     */
    public function get(array $options = [])
    {
        $response = $this->getClient()->get($this->getRoute(), $options);
        $response = $this->handleResponse($response);
        // If response is not successful only
        if (\Configuration::get('PS_ACCOUNTS_DEBUG_LOGS_ENABLED') && !$response['status']) {
            /**
             * @var \Ps_accounts
             */
            $module = \Module::getInstanceByName('ps_accounts');
            $logger = $module->getLogger();
            $logger->debug('route ' . $this->getRoute());
            $logger->debug('options ' . var_export($options, true));
            $logger->debug('response ' . var_export($response, true));
        }

        return $response;
    }

    /**
     * Wrapper of method delete from guzzle client.
     *
     * @param array $options payload
     *
     * @return array return response array
     */
    public function delete(array $options = [])
    {
        $response = $this->getClient()->delete($this->getRoute(), $options);
        $response = $this->handleResponse($response);
        // If response is not successful only
        if (\Configuration::get('PS_ACCOUNTS_DEBUG_LOGS_ENABLED') && !$response['status']) {
            /**
             * @var \Ps_accounts
             */
            $module = \Module::getInstanceByName('ps_accounts');
            $logger = $module->getLogger();
            $logger->debug('route ' . $this->getRoute());
            $logger->debug('options ' . var_export($options, true));
            $logger->debug('response ' . var_export($response, true));
        }

        return $response;
    }

    /**
     * Check if the response is successful or not (response code 200 to 299).
     *
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
     * Setter for client.
     *
     * @param Client $client
     *
     * @return void
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Getter for route.
     *
     * @return string
     */
    protected function getRoute()
    {
        return $this->route;
    }

    /**
     * Setter for route.
     *
     * @param string $route
     *
     * @return void
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }
}

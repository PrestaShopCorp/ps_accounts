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

namespace PrestaShop\Module\PsAccounts\Api\Client;

use PrestaShop\Module\PsAccounts\Http\Client\ClientConfig;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Http\Client\Response;

class ExternalAssetsClient
{
    /**
     * @var \Ps_accounts
     */
    private $module;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    protected $clientConfig;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');
        $this->module = $module;

        $this->clientConfig = array_merge([
            ClientConfig::NAME => static::class,
            ClientConfig::HEADERS => $this->getHeaders(),
        ], $config);
    }

    /**
     * @return Client
     */
    private function getClient()
    {
        if (null === $this->client) {
            $this->client = (new Factory())->create($this->clientConfig);
        }

        return $this->client;
    }

    /**
     * @param array $additionalHeaders
     *
     * @return array
     */
    private function getHeaders($additionalHeaders = [])
    {
        return array_merge([
            'Accept' => 'application/json',
        ], $additionalHeaders);
    }

    /**
     * @return Response
     */
    public function getTestimonials()
    {
        return $this->getClient()->get(
            $this->module->getParameter('ps_accounts.testimonials_url')
        );
    }
}

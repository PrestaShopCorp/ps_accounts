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

use PrestaShop\Module\PsAccounts\Api\Client\CircuitBreaker\CircuitBreaker;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\GuzzleClient;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\Factory\CircuitBreakerFactory;
use PrestaShop\Module\PsAccounts\Repository\TokenClientInterface;

/**
 * Class ServicesAccountsClient
 */
class SsoClient implements TokenClientInterface
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var CircuitBreaker
     */
    private $circuitBreaker;

    /**
     * @var mixed
     */
    private $defaultTimeout;

    /**
     * ServicesAccountsClient constructor.
     *
     * @param string $apiUrl
     * @param GuzzleClient|null $client
     * @param int $defaultTimeout
     *
     * @throws \Exception
     */
    public function __construct(
        $apiUrl,
        GuzzleClient $client = null,
        $defaultTimeout = 20
    ) {
        $this->apiUrl = $apiUrl;
        $this->client = $client;
        $this->circuitBreaker = CircuitBreakerFactory::create('SSO_CLIENT');
        $this->defaultTimeout = $defaultTimeout;
    }

    /**
     * @return GuzzleClient
     */
    private function getClient()
    {
        if (null === $this->client) {
            $this->client = (new GuzzleClientFactory())->create([
//                'base_url' => $this->apiUrl,
//                'defaults' => [
//                    'headers' => [
//                        'Accept' => 'application/json',
//                        'X-Module-Version' => \Ps_accounts::VERSION,
//                        'X-Prestashop-Version' => _PS_VERSION_,
//                    ],
//                    'timeout' => $this->defaultTimeout,
//                ],
                'base_uri' => $this->apiUrl,
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Module-Version' => \Ps_accounts::VERSION,
                    'X-Prestashop-Version' => _PS_VERSION_,
                ],
                'timeout' => $this->defaultTimeout,
            ]);
        }

        return $this->client;
    }

    /**
     * @param string $idToken
     *
     * @return array response
     */
    public function verifyToken($idToken)
    {
        $this->getClient()->setRoute('auth/token/verify');

        return $this->getClient()->post([
            'json' => [
                'token' => $idToken,
            ],
        ]);
    }

    /**
     * @param string $refreshToken
     *
     * @return array response
     */
    public function refreshToken($refreshToken)
    {
        return $this->circuitBreaker->call(function () use ($refreshToken) {
            $this->getClient()->setRoute('auth/token/refresh');

            return $this->getClient()->post([
                'json' => [
                    'token' => $refreshToken,
                ],
            ]);
        });
    }

    /**
     * @return CircuitBreaker
     */
    public function getCircuitBreaker()
    {
        return $this->circuitBreaker;
    }
}

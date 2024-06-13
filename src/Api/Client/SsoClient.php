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

use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\AbstractGuzzleClient;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\TokenClientInterface;

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
     * @var AbstractGuzzleClient
     */
    private $client;

    public function __construct(
        string $apiUrl,
        ?AbstractGuzzleClient $client = null
    ) {
        $this->apiUrl = $apiUrl;
        $this->client = $client;
    }

    public function verifyToken(string $idToken): array
    {
        $this->getClient()->setRoute('auth/token/verify');

        return $this->getClient()->post([
            'json' => [
                'token' => $idToken,
            ],
        ]);
    }

    public function refreshToken(string $refreshToken): array
    {
        $this->getClient()->setRoute('auth/token/refresh');

        return $this->getClient()->post([
            'json' => [
                'token' => $refreshToken,
            ],
        ]);
    }

    private function getClient(): AbstractGuzzleClient
    {
        if (null === $this->client) {
            $this->client = (new GuzzleClientFactory())->create([
                'base_url' => $this->apiUrl,
                'defaults' => [
                    'headers' => [
                        'Accept' => 'application/json',
                        'X-Module-Version' => \Ps_accounts::VERSION,
                        'X-Prestashop-Version' => _PS_VERSION_,
                    ],
                ],
            ]);
        }

        return $this->client;
    }
}

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

use Module;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\AbstractGuzzleClient;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\TokenClientInterface;
use PrestaShop\Module\PsAccounts\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

/**
 * Class ServicesAccountsClient
 */
class AccountsClient implements TokenClientInterface
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    /**
     * @var AbstractGuzzleClient
     */
    private $client;

    public function __construct(
        string $apiUrl,
        ConfigurationRepository $configurationRepository,
        ?AbstractGuzzleClient $client = null
    ) {
        $this->apiUrl = $apiUrl;
        $this->configurationRepository = $configurationRepository->getShopId();
        $this->client = $client;
    }

    public function verifyToken(string $idToken): array
    {
        $this->getClient()->setRoute('shop/token/verify');

        return $this->getClient()->post([
            'json' => [
                'headers' => $this->getHeaders([
                    'X-Shop-Id' => $this->configurationRepository->getShopId(),
                ]),
                'token' => $idToken,
            ],
        ]);
    }

    public function refreshToken(string $refreshToken): array
    {
        $this->getClient()->setRoute('shop/token/refresh');

        return $this->getClient()->post([
            'json' => [
                'headers' => $this->getHeaders([
                    'X-Shop-Id' => $this->configurationRepository->getShopId(),
                ]),
                'token' => $refreshToken,
            ],
        ]);
    }

    public function deleteUserShop(string $ownerUid, string $shopUid, string $ownerToken): array
    {
        $this->client->setRoute('user/' . $ownerUid . '/shop/' . $shopUid);

        return $this->getClient()->delete([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $ownerToken,
                'X-Shop-Id' => $this->configurationRepository->getShopId(),
            ]),
        ]);
    }

    public function reonboardShop(string $shopUid, string $shopToken, array $payload): array
    {
        $this->getClient()->setRoute('shop/' . $shopUid . '/reonboard');

        return $this->getClient()->post([
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $shopToken,
                    'X-Shop-Id' => $payload['id'],
                ]),
                'json' => $payload,
            ]);
    }

    public function updateUserShop(string $ownerUid, string $shopUid, string $ownerToken, UpdateShop $shop): array
    {
        $this->getClient()->setRoute('user/' . $ownerUid . '/shop/' . $shopUid);

        return $this->getClient()->patch([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $ownerToken,
                'X-Shop-Id' => $shop->shopId,
            ]),
            'json' => $shop->jsonSerialize(),
        ]);
    }

    private function getHeaders(array $additionalHeaders = []): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'X-Module-Version' => \Ps_accounts::VERSION,
            'X-Prestashop-Version' => _PS_VERSION_,
        ], $additionalHeaders);
    }

    private function getClient(): AbstractGuzzleClient
    {
        if (null === $this->client) {
            $this->client = (new GuzzleClientFactory())->create([
                'base_url' => $this->apiUrl,
                'defaults' => [
                    'headers' => $this->getHeaders(),
                ],
            ]);
        }

        return $this->client;
    }
}

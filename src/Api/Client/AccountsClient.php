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
use PrestaShop\Module\PsAccounts\Dto\UpdateShop;

/**
 * Class ServicesAccountsClient
 */
class AccountsClient implements TokenClientInterface
{
    /**
     * @var AbstractGuzzleClient
     */
    private $client;

    public function __construct(
        string                $apiUrl,
        ?AbstractGuzzleClient $client = null
    ) {
        if (null === $client) {
            $client = (new GuzzleClientFactory())->create([
                'base_url' => $apiUrl,
            ]);
        }

        $this->client = $client;
    }

    public function verifyToken(string $idToken): array
    {
        $this->client->setRoute('shop/token/verify');

        return $this->client->post([
            'json' => [
                'headers' => $this->getHeaders(),
                'token' => $idToken,
            ],
        ]);
    }

    public function refreshToken(string $refreshToken): array
    {
        $this->client->setRoute('shop/token/refresh');

        return $this->client->post([
            'json' => [
                'headers' => $this->getHeaders(),
                'token' => $refreshToken,
            ],
        ]);
    }

    public function deleteUserShop(string $ownerUid, string $shopUid, string $ownerToken): array
    {
        $this->client->setRoute('user/' . $ownerUid . '/shop/' . $shopUid);

        return $this->client->delete([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $ownerToken,
            ]),
        ]);
    }

    public function reonboardShop(string $shopUid, string $shopToken, array $payload): array
    {
        $this->client->setRoute('shop/' . $shopUid . '/reonboard');

        return $this->client->post([
                'headers' => $this->getHeaders([
                    'Authorization' => 'Bearer ' . $shopToken,
                    'content-type' => 'application/json',
                ]),
                'json' => $payload,
            ]);
    }

    public function updateUserShop(string $ownerUid, string $shopUid, string $ownerToken, UpdateShop $shop): array
    {
        $this->client->setRoute('user/' . $ownerUid . '/shop/' . $shopUid);

        return $this->client->patch([
            'headers' => $this->getHeaders([
                'Authorization' => 'Bearer ' . $ownerToken,
                'content-type' => 'application/json',
            ]),
            'json' => $shop->jsonSerialize(),
        ]);
    }

    private function getHeaders(array $additionalHeaders = []): array
    {
        return array_merge([
            'Accept' => 'application/json',
        ], $additionalHeaders);
    }
}

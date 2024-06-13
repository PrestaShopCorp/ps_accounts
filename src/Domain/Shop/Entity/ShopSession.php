<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Entity;

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\SessionInterface;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ShopSession extends AbstractSession implements SessionInterface
{
    /**
     * @var AccountsClient
     */
    protected $apiClient;

    public function __construct(AccountsClient $apiClient, ConfigurationRepository $configurationRepository)
    {
        parent::__construct($apiClient, $configurationRepository);
    }

    public static function getSessionName(): string
    {
        return 'shop';
    }

    public function getToken(): Token
    {
        return new Token(
            $this->configurationRepository->getFirebaseIdToken(),
            $this->configurationRepository->getFirebaseRefreshToken()
        );
    }

    public function cleanup(): void
    {
        $this->configurationRepository->updateShopUuid('');
        $this->configurationRepository->updateFirebaseIdAndRefreshTokens('', '');
    }

    public function setToken(string $token, string $refreshToken): void
    {
        $parsed = (new Parser())->parse($token);

        $this->configurationRepository->updateShopUuid($parsed->claims()->get('user_id'));
        $this->configurationRepository->updateFirebaseIdAndRefreshTokens($token, $refreshToken);
    }

    protected function getTokenFromRefreshResponse(array $response): Token
    {
        return new Token(
            $response['body']['token'],
            $response['body']['refresh_token']
        );
    }
}

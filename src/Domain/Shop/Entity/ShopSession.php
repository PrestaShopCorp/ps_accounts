<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Entity;

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\SessionInterface;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ShopSession extends AbstractSession implements SessionInterface
{
    protected const TOKEN_TYPE = 'shop';
    protected const RESPONSE_TOKEN_KEY = 'token';
    protected const RESPONSE_REFRESH_TOKEN_KEY = 'refresh_token';

    /**
     * @var AccountsClient
     */
    protected $apiClient;

    public function __construct(AccountsClient $apiClient, ConfigurationRepository $configurationRepository)
    {
        parent::__construct($apiClient, $configurationRepository);
    }

    public function getToken(): Token
    {
        return new Token(
            $this->configurationRepository->getFirebaseIdToken(),
            $this->configurationRepository->getFirebaseRefreshToken()
        );
    }

    /**
     * @return void
     */
    public function cleanup(): void
    {
        $this->configurationRepository->updateShopUuid('');
        $this->configurationRepository->updateFirebaseIdAndRefreshTokens('', '');
    }

    /**
     * @param string $token
     * @param string $refreshToken
     *
     * @return void
     */
    public function setToken(string $token, string $refreshToken)
    {
        $parsed = (new Parser())->parse($token);

        $this->configurationRepository->updateShopUuid($parsed->claims()->get('user_id'));
        $this->configurationRepository->updateFirebaseIdAndRefreshTokens($token, $refreshToken);
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PrestaShopSession
{
    const TOKEN_NAME = 'accessToken';

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var PrestaShop
     */
    private $provider;

    public function __construct(SessionInterface $session, PrestaShop $provider)
    {
        $this->session = $session;
        $this->provider = $provider;
    }

    /**
     * @throws IdentityProviderException
     */
    public function getOrRefreshAccessToken(): ?string
    {
        $token = $this->getTokenProvider();
        if ($token && $token->hasExpired()) {
            /** @var AccessToken $token */
            $token = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $token->getRefreshToken(),
            ]);
            $this->setTokenProvider($token);
        }

        return $this->getAccessToken();
    }

    public function getIdToken(): ?string
    {
        $token = $this->getTokenProvider();

        return $token ? $token->getValues()['id_token'] : null;
    }

    public function getAccessToken(): ?string
    {
        $token = $this->getTokenProvider();

        return $token ? $token->getToken() : null;
    }

    public function setTokenProvider(AccessToken $token): void
    {
        $this->session->set(self::TOKEN_NAME, $token);
    }

    public function isAuthenticated(): bool
    {
        return $this->session->has(self::TOKEN_NAME);
    }

    public function getPrestashopUser(): PrestaShopUser
    {
        return $this->provider->getResourceOwner($this->getTokenProvider());
    }

    public function clear(): void
    {
        $this->session->remove(self::TOKEN_NAME);
    }

    private function getTokenProvider(): ?AccessToken
    {
        return $this->session->get(self::TOKEN_NAME);
    }
}

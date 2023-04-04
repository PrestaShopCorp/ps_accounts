<?php

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use League\OAuth2\Client\Token\AccessToken;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OAuth2Session
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

    public function getOrRefreshAccessToken(): ?AccessToken
    {
        $token = $this->getAccessToken();
        if ($token->hasExpired()) {
            $token = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $token->getRefreshToken(),
            ]);
            $this->setAccessToken($token);
        }

        return $token;
    }

    public function getAccessToken(): ?AccessToken
    {
        /** @var AccessToken $token */
        $token = $this->session->get(self::TOKEN_NAME);

        return $token;
    }

    public function setAccessToken(AccessToken $token)
    {
        $this->session->set(self::TOKEN_NAME, $token);
    }

    public function clear()
    {
        $this->session->remove(self::TOKEN_NAME);
    }
}

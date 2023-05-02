<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Entity;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token\InvalidTokenStructure;

class Token
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $refreshToken;

    public function __construct(string $token, string $refreshToken)
    {
        $this->token = $token;
        $this->refreshToken = $refreshToken;
    }

    public function getJwt(): \Lcobucci\JWT\Token
    {
        return $this->parseToken($this->token);
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function isExpired(): bool
    {
        $token = $this->getJwt();

        return $token->isExpired(new \DateTime());
    }

    public function getUuid(): ?string
    {
        // return $this->configuration->getShopUuid();
        // return $this->configuration->getUserFirebaseUuid();
        // return $this->getToken()->claims()->get('user_id');
        // FIXME: sub ?
        return $this->getJwt()->claims()->get('user_id');
    }

    public function getEmail(): ?string
    {
        // return $this->configuration->getFirebaseEmail();
        return $this->getJwt()->claims()->get('email');
    }

    public function __toString(): string
    {
        return $this->token;
    }

    protected function parseToken(string $token): \Lcobucci\JWT\Token
    {
        try {
            return (new Parser())->parse((string) $token);
        } catch (InvalidTokenStructure $e) {
            return $this->getNullToken();
        }
    }

    protected function getNullToken(): \Lcobucci\JWT\Token
    {
        //return new \Lcobucci\JWT\Token([], ['exp' => new \DateTime()]);
        return new NullToken([], ['exp' => new \DateTime()]);
    }
}

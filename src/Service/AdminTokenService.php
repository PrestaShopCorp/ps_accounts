<?php

namespace PrestaShop\Module\PsAccounts\Service;

use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\Clock\FrozenClock;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Builder;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Configuration;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Signer\Hmac\Sha256;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Signer\Key\InMemory;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Validation\Constraint\SignedWith;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Validation\Constraint\ValidAt;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Validation\NoConstraintsGiven;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Validation\RequiredConstraintsViolated;

class AdminTokenService
{
    const PS_ACCOUNTS_TOKEN_SIGNATURE = 'PS_ACCOUNTS_TOKEN_SIGNATURE';

    /**
     * @return Token
     */
    public function getToken()
    {
        $signature = $this->getTokenSignature();
        if (!$signature) {
            $signature = $this->generateSignature();
            $this->setTokenSignature($signature);
        }

        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($signature)
        );

        $issuedAt = new \DateTimeImmutable();

        $builder = (new Builder())
            ->issuedAt($issuedAt)
            ->expiresAt($issuedAt->modify('+1 hour'));

        return $builder->getToken(
            $configuration->signer(),
            $configuration->signingKey()
        );
    }

    /**
     * @param string $token
     *
     * @return bool
     *
     * @throws RequiredConstraintsViolated
     * @throws NoConstraintsGiven
     */
    public function verifyToken($token)
    {
        $signature = $this->getTokenSignature();

        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($signature)
        );

        $configuration->setValidationConstraints(
            new SignedWith($configuration->signer(), $configuration->signingKey()),
            new ValidAt(new FrozenClock(new \DateTimeImmutable()))
        );

        $token = $configuration->parser()->parse($token);

        $constraints = $configuration->validationConstraints();

        $configuration->validator()->assert($token, ...$constraints);

        return true;
    }

    /**
     * @return string
     */
    protected function generateSignature()
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }

    /**
     * @return string
     */
    protected function getTokenSignature()
    {
        return \Configuration::getGlobalValue(self::PS_ACCOUNTS_TOKEN_SIGNATURE);
    }

    /**
     * @param string $signature
     *
     * @return void
     */
    protected function setTokenSignature($signature)
    {
        \Configuration::updateGlobalValue(self::PS_ACCOUNTS_TOKEN_SIGNATURE, $signature);
    }
}

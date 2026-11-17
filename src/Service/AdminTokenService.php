<?php

namespace PrestaShop\Module\PsAccounts\Service;

use PrestaShop\Module\PsAccounts\Polyfill\UtcClock;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\Clock\Clock;
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
     * @var Clock
     */
    private $clock;

    /**
     * @param Clock|null $clock the shared UTC system clock by default
     *
     * @throws \InvalidArgumentException when $clock is neither null nor a Clock
     */
    public function __construct($clock = null)
    {
        $this->clock = UtcClock::orDefault($clock);
    }

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

        // format('U'): getTimestamp() is one hour off during the DST fall-back
        // on PHP < 8.1 for a clock in a local timezone
        $now = (int) $this->clock->now()->format('U');

        $builder = (new Builder())
            ->issuedAt(new \DateTimeImmutable('@' . $now))
            ->expiresAt(new \DateTimeImmutable('@' . ($now + 3600)));

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
            new ValidAt($this->clock)
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

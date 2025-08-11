<?php

namespace PrestaShop\Module\PsAccounts\Service;

use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\Clock\FrozenClock;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Builder;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Configuration;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Signer\Hmac\Sha256;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Signer\Key\InMemory;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Validation\Constraint\SignedWith;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Validation\Constraint\ValidAt;

class TokenService
{
    const PS_ACCOUNTS_TOKEN_SIGNATURE = 'PS_ACCOUNTS_TOKEN_SIGNATURE';

    /**
     * @var ConfigurationRepository
     */
    private $repository;

    /**
     * @param ConfigurationRepository $configurationRepository
     */
    public function __construct(ConfigurationRepository $configurationRepository)
    {
        $this->repository = $configurationRepository;
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        $signature = $this->getTokenSignature();
        Logger::getInstance()->error('Token signature 1', ['signature' => $signature]);
        if (!$signature) {
            $signature = base64_encode(hash('sha256', (string) mt_rand())); // TODO: use openssl ?
            $this->setTokenSignature($signature);
        }
        Logger::getInstance()->error('Token signature 2 ', ['signature' => $signature]);

        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::base64Encoded($signature)
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
     */
    public function verifyToken($token)
    {
        $signature = $this->getTokenSignature();

        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::base64Encoded($signature)
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

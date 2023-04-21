<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Entity;

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\SessionInterface;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class OwnerSession extends AbstractSession implements SessionInterface
{
    protected const TOKEN_TYPE = 'user';
    protected const RESPONSE_TOKEN_KEY = 'idToken';
    protected const RESPONSE_REFRESH_TOKEN_KEY = 'refreshToken';

    /**
     * @var SsoClient
     */
    protected $apiClient;

    public function __construct(SsoClient $apiClient, ConfigurationRepository $configurationRepository)
    {
        parent::__construct($apiClient, $configurationRepository);
    }

    public function getToken(): Token
    {
        return new Token(
            $this->configurationRepository->getUserFirebaseIdToken(),
            $this->configurationRepository->getUserFirebaseRefreshToken()
        );
    }

    /**
     * @return void
     */
    public function cleanup(): void
    {
        $this->configurationRepository->updateUserFirebaseUuid('');
        $this->configurationRepository->updateUserFirebaseIdToken('');
        $this->configurationRepository->updateUserFirebaseRefreshToken('');
        $this->configurationRepository->updateFirebaseEmail('');
        $this->configurationRepository->updateEmployeeId('');
        //$this->configuration->updateFirebaseEmailIsVerified(false);
    }

    /**
     * @param string $token
     * @param string $refreshToken
     *
     * @return void
     */
    public function setToken(string $token, string $refreshToken)
    {
        $parsed = (new Parser())->parse((string) $token);

        $uuid = $parsed->claims()->get('user_id');
        $this->configurationRepository->updateUserFirebaseUuid($uuid);
        $this->configurationRepository->updateUserFirebaseIdToken($token);
        $this->configurationRepository->updateUserFirebaseRefreshToken($refreshToken);

        $this->configurationRepository->updateFirebaseEmail($parsed->claims()->get('email'));
    }

    public function getEmployeeId(): ?int
    {
        return (int) $this->configurationRepository->getEmployeeId();
    }

    public function setEmployeeId(?int $employeeId): void
    {
        $this->configurationRepository->updateEmployeeId($employeeId);
    }
}


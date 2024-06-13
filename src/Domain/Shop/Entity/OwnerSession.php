<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Entity;

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\SessionInterface;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class OwnerSession extends AbstractSession implements SessionInterface
{
    /**
     * @var SsoClient
     */
    protected $apiClient;

    public function __construct(SsoClient $apiClient, ConfigurationRepository $configurationRepository)
    {
        parent::__construct($apiClient, $configurationRepository);
    }

    public static function getSessionName(): string
    {
        return 'user';
    }

    public function getToken(): Token
    {
        return new Token(
            $this->configurationRepository->getUserFirebaseIdToken(),
            $this->configurationRepository->getUserFirebaseRefreshToken()
        );
    }

    public function cleanup(): void
    {
        $this->configurationRepository->updateUserFirebaseUuid('');
        $this->configurationRepository->updateUserFirebaseIdToken('');
        $this->configurationRepository->updateUserFirebaseRefreshToken('');
        $this->configurationRepository->updateFirebaseEmail('');
        $this->configurationRepository->updateEmployeeId('');
        //$this->configuration->updateFirebaseEmailIsVerified(false);
    }

    public function setToken(string $token, string $refreshToken): void
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
        $this->configurationRepository->updateEmployeeId((string) $employeeId);
    }

    protected function getTokenFromRefreshResponse(array $response): Token
    {
        return new Token(
            $response['body']['idToken'],
            $response['body']['refreshToken']
        );
    }
}

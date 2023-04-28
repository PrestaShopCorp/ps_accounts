<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\Entity;

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class Oauth2Client
{
    /**
     * @var ConfigurationRepository
     */
    private $configurationRepository;

    public function __construct(ConfigurationRepository $configurationRepository)
    {
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * @throws \Exception
     */
    public function exists(): bool
    {
        return (bool) $this->configurationRepository->getOauth2ClientId();
    }

    /**
     * @throws \Exception
     */
    public function delete(): void
    {
        $this->configurationRepository->updateOauth2ClientId('');
        $this->configurationRepository->updateOauth2ClientSecret('');
    }

    public function update(string $clientId, string $clientSecret): void
    {
        $this->configurationRepository->updateOauth2ClientId($clientId);
        $this->configurationRepository->updateOauth2ClientSecret($clientSecret);
    }

    public function getClientId(): string
    {
        return $this->configurationRepository->getOauth2ClientId();
    }

    public function getClientSecret(): string
    {
        return $this->configurationRepository->getOauth2ClientSecret();
    }
}

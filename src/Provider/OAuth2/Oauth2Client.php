<?php

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class Oauth2Client
{
    /**
     * @var ConfigurationRepository
     */
    private $cfRepos;

    public function __construct(ConfigurationRepository $configurationRepository)
    {
        $this->cfRepos = $configurationRepository;
    }

    /**
     * @throws \Exception
     */
    public function exists(): bool
    {
        return (bool) $this->cfRepos->getOauth2ClientId();
    }

    /**
     * @throws \Exception
     */
    public function delete(): void
    {
        $this->cfRepos->updateOauth2ClientId('');
        $this->cfRepos->updateOauth2ClientSecret('');
    }

    public function update(string $clientId, string $clientSecret): void
    {
        $this->cfRepos->updateOauth2ClientId($clientId);
        $this->cfRepos->updateOauth2ClientSecret($clientSecret);
    }

    public function getClientId(): string
    {
        return $this->cfRepos->getOauth2ClientId();
    }

    public function getClientSecret(): string
    {
        return $this->cfRepos->getOauth2ClientSecret();
    }
}

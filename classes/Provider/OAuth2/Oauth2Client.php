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
     *
     * @return bool
     */
    public function exists()
    {
        return (bool) $this->cfRepos->getOauth2ClientId();
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function delete()
    {
        $this->cfRepos->updateOauth2ClientId('');
        $this->cfRepos->updateOauth2ClientSecret('');
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return void
     */
    public function update($clientId, $clientSecret)
    {
        $this->cfRepos->updateOauth2ClientId($clientId);
        $this->cfRepos->updateOauth2ClientSecret($clientSecret);
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->cfRepos->getOauth2ClientId();
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->cfRepos->getOauth2ClientSecret();
    }
}

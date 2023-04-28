<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Domain\Account\Command\RegisterOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Oauth2Client;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class RegisterOauth2ClientCommandHandler
{
    /**
     * @var Oauth2Client
     */
    private $oauth2Client;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @param Oauth2Client $oauth2Client
     * @param ConfigurationRepository $configurationRepository
     */
    public function __construct(Oauth2Client $oauth2Client, ConfigurationRepository $configurationRepository)
    {
        $this->oauth2Client = $oauth2Client;
        $this->configuration = $configurationRepository;
    }

    public function handle(RegisterOauth2ClientCommand $command): void
    {
        $this->oauth2Client->update($command->clientId, $command->clientSecret);
        $this->configuration->updateLoginEnabled(true);
    }
}

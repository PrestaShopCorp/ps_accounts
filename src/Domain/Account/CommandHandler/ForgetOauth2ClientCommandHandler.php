<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Oauth2Client;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ForgetOauth2ClientCommandHandler
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

    /**
     * @throws \Exception
     */
    public function handle(ForgetOauth2ClientCommand $command): void
    {
        if ($this->oauth2Client->exists()) {
            $this->oauth2Client->delete();
            $this->configuration->updateLoginEnabled(false);
        }
    }
}

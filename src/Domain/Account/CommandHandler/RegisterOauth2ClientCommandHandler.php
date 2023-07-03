<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Domain\Account\Command\EnableLoginCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Command\RegisterOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Oauth2Client;

class RegisterOauth2ClientCommandHandler
{
    /**
     * @var Oauth2Client
     */
    private $oauth2Client;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @param Oauth2Client $oauth2Client
     * @param CommandBus $commandBus
     */
    public function __construct(Oauth2Client $oauth2Client, CommandBus $commandBus)
    {
        $this->oauth2Client = $oauth2Client;
        $this->commandBus = $commandBus;
    }

    /**
     * @throws \Exception
     */
    public function handle(RegisterOauth2ClientCommand $command): void
    {
        $this->oauth2Client->update($command->clientId, $command->clientSecret);
        $this->commandBus->handle(new EnableLoginCommand());
    }
}

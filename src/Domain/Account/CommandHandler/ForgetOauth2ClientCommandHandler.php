<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Domain\Account\Command\DisableLoginCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Oauth2Client;

class ForgetOauth2ClientCommandHandler
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
    public function handle(ForgetOauth2ClientCommand $command): void
    {
        if ($this->oauth2Client->exists()) {
            $this->oauth2Client->delete();
            $this->commandBus->handle(new DisableLoginCommand());
        }
    }
}

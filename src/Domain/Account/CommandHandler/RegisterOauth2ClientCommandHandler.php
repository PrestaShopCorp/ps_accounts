<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Domain\Account\Command\RegisterOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Login;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Oauth2Client;

class RegisterOauth2ClientCommandHandler
{
    /**
     * @var Oauth2Client
     */
    private $oauth2Client;

    /**
     * @var Login
     */
    private $login;

    /**
     * @param Oauth2Client $oauth2Client
     * @param Login $login
     */
    public function __construct(Oauth2Client $oauth2Client, Login $login)
    {
        $this->oauth2Client = $oauth2Client;
        $this->login = $login;
    }

    public function handle(RegisterOauth2ClientCommand $command): void
    {
        $this->oauth2Client->update($command->clientId, $command->clientSecret);
        $this->login->enable();
    }
}

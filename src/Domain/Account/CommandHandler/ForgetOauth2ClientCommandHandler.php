<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Login;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Oauth2Client;

class ForgetOauth2ClientCommandHandler
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

    /**
     * @throws \Exception
     */
    public function handle(ForgetOauth2ClientCommand $command): void
    {
        if ($this->oauth2Client->exists()) {
            $this->oauth2Client->delete();
            $this->login->disable();
        }
    }
}

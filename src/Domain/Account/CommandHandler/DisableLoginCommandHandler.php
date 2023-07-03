<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Domain\Account\Command\DisableLoginCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Login;

class DisableLoginCommandHandler
{
    /**
     * @var Login
     */
    private $login;

    /**
     * @param Login $login
     */
    public function __construct(Login $login)
    {
        $this->login = $login;
    }

    /**
     * @throws \Exception
     */
    public function handle(DisableLoginCommand $command): void
    {
        $this->login->disable();
    }
}

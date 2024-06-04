<?php

namespace PrestaShop\Module\PsAccounts\Domain\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Domain\Account\Command\EnableLoginCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Login;

class EnableLoginHandler
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
    public function handle(EnableLoginCommand $command): void
    {
        $this->login->enable();
    }
}

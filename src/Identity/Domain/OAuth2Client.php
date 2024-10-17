<?php

namespace PrestaShop\Module\PsAccounts\Identity\Domain;

class OAuth2Client {
    private $id;
    private $secret;

    public function __construct($id, $secret)
    {
        $this->id = $id;
        $this->secret = $secret;
    }

    public function id()
    {
        return $this->id;
    }

    public function secret()
    {
        return $this->secret;
    }
}

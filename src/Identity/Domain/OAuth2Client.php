<?php

namespace PrestaShop\Module\PsAccounts\Identity\Domain;

class OAuth2Client {
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $secret;

    /**
     * OAuth2Client constructor
     *
     * @param string $id
     * @param string $secret
     */
    public function __construct($id, $secret)
    {
        $this->id = $id;
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function secret()
    {
        return $this->secret;
    }
}

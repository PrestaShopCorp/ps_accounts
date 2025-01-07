<?php

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

class AccessToken
{
    /**
     * @var string
     */
    public $access_token;

    /**
     * @var string
     */
    public $refresh_token;

    /**
     * @var string
     */
    public $id_token;

    /**
     * @var string
     */
    public $scope;

    /**
     * @var string;
     */
    public $token_type;

    /**
     * @var string
     */
    public $expires;

    /**
     * @var string
     */
    public $expires_in;

    /**
     * @var string
     */
    public $resource_owner_id;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

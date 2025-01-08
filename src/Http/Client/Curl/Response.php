<?php

namespace PrestaShop\Module\PsAccounts\Http\Client\Curl;

class Response
{
    /**
     * @var array
     */
    public $body;

    /**
     * @var int
     */
    public $httpCode;

    /**
     * @var bool
     */
    public $status;

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

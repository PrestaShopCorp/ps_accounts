<?php

namespace PrestaShop\Module\PsAccounts\Api\Client;

class ShopInterface
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string;
     */
    public $name;

    /**
     * @var string
     */
    public $domain;

    /**
     * @var string
     */
    public $domainSsl;

    /**
     * @var string
     */
    public $physicalUri;

    /**
     * @var string
     */
    public $virtualUri;

    /**
     * @var string
     */
    public $uuid;

    /**
     * @var string
     */
    public $publicKey;

    /**
     * @var string
     */
    public $employeeId;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $mandatory = ['id'];

    /**
     * @param array $values
     *
     * @throws \Exception
     */
    public function __construct($values = [])
    {
        foreach ($values as $attrName => $attrValue) {
            if (! property_exists($this, $attrName)) {
                throw new \Exception('property does not exists : ' . $attrName);
            }
            $this->$attrName = $attrValue;
            $this->attributes[] = $attrName;
        }

        foreach ($this->mandatory as $attrName => $attrValue) {
            if (! in_array($attrName, $this->attributes)) {
                throw new \Exception('mandatory property missing : ' . $attrName);
            }
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_filter((array) $this, function ($attrValue, $attrName) {
            return in_array($attrName, $this->attributes);
        });
    }
}

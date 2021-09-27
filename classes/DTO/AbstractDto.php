<?php

namespace PrestaShop\Module\PsAccounts\DTO;

abstract class AbstractDto implements \JsonSerializable
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $mandatory = [];

    /**
     * @param array $values
     *
     * @throws \Exception
     */
    public function __construct($values = [])
    {
        foreach ($values as $attrName => $attrValue) {
            if (!property_exists($this, $attrName)) {
                throw new \Exception('unexpected property : ' . get_class($this) . '->' . $attrName);
            }
            $this->$attrName = $attrValue;
            $this->attributes[] = $attrName;
        }

        foreach ($this->mandatory as $attrName) {
            if (!in_array($attrName, $this->attributes)) {
                throw new \Exception('property expected : ' . get_class($this) . '->' . $attrName);
            }
        }
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return array_filter((array) $this, function ($attrValue, $attrName) {
            return in_array($attrName, $this->attributes);
        }, ARRAY_FILTER_USE_BOTH);
    }
}

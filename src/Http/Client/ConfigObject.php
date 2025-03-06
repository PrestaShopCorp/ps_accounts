<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Http\Client;

use PrestaShop\Module\PsAccounts\Http\Client\Exception\RequiredPropertyException;
use PrestaShop\Module\PsAccounts\Http\Client\Exception\UndefinedPropertyException;
use PrestaShop\Module\PsAccounts\Type\Enum;

class ConfigObject extends Enum
{
    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * @var array
     */
    protected $required = [];

    /**
     * @param array $values
     *
     * @throws RequiredPropertyException
     * @throws UndefinedPropertyException
     */
    public function __construct(array $values)
    {
        foreach (array_merge($this->defaults, $values) as $name => $value) {
            $this->assertPropertyExists($name);

            $this->$name = $value;
        }
        $this->assertRequiredProperties();
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws UndefinedPropertyException
     */
    public function __get($name)
    {
        $this->assertPropertyExists($name);

        return $this->$name;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return void
     *
     * @throws UndefinedPropertyException
     */
    public function __set($name, $value)
    {
        $this->assertPropertyExists($name);

        $this->$name = $value;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($property) {
            return $this->$property;
        }, $this->properties);
    }

    /**
     * @return array
     */
    protected function getProperties()
    {
        if (empty($this->properties)) {
            $this->properties = static::values();
        }

        return $this->properties;
    }

    /**
     * @param string $name
     *
     * @return void
     *
     * @throws UndefinedPropertyException
     */
    protected function assertPropertyExists($name)
    {
        if (!in_array($name, $this->getProperties(), true)) {
            throw new UndefinedPropertyException('Trying to access undefined property : ' . $name . '.');
        }
    }

    /**
     * @return void
     *
     * @throws RequiredPropertyException
     */
    protected function assertRequiredProperties()
    {
        foreach ($this->required as $name) {
            if (!property_exists($this, $name)) {
                throw new RequiredPropertyException('Missing required property : ' . $name . '.');
            }
        }
    }
}

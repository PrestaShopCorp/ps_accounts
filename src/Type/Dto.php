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

namespace PrestaShop\Module\PsAccounts\Type;

use PrestaShop\Module\PsAccounts\Exception\DtoException;

abstract class Dto implements \JsonSerializable
{
    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * @var string[]
     */
    protected $required = [];

    /**
     * @var bool
     */
    protected $throwOnUnexpectedProperties = true;

    /**
     * @param array $values
     *
     * @throws DtoException
     */
    public function __construct($values = [])
    {
        foreach (array_merge($this->defaults, $values) as $name => $value) {
            $this->assertUnexpectedProperty($name);

            $this->$name = $value;
            $this->properties[] = $name;
        }
        $this->assertRequiredProperties();
    }

    /**
     * @return array|mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_filter((array) $this, function ($attrValue, $attrName) {
            return in_array($attrName, $this->properties);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * @return void
     *
     * @throws DtoException
     */
    protected function assertRequiredProperties()
    {
        foreach ($this->required as $name) {
            if (!in_array($name, $this->properties)) {
                throw new DtoException('Missing required property : ' . get_class($this) . '::' . $name);
            }
        }
    }

    /**
     * @param string $name
     *
     * @return void
     *
     * @throws DtoException
     */
    protected function assertUnexpectedProperty($name)
    {
        if (!property_exists($this, $name) &&
            $this->throwOnUnexpectedProperties) {
            throw new DtoException('Unexpected property : ' . get_class($this) . '::' . $name);
        }
    }
}

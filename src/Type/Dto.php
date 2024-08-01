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
    protected $attributes = [];

    /**
     * @var string[]
     */
    protected $mandatory = [];

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
        foreach ($values as $attrName => $attrValue) {
            if (property_exists($this, $attrName)) {
                $this->$attrName = $attrValue;
                $this->attributes[] = $attrName;
            } elseif ($this->throwOnUnexpectedProperties) {
                throw new DtoException('unexpected property : ' . get_class($this) . '->$' . $attrName);
            }
        }

        foreach ($this->mandatory as $attrName) {
            if (!in_array($attrName, $this->attributes)) {
                throw new DtoException('property expected : ' . get_class($this) . '->$' . $attrName);
            }
        }
    }

    /**
     * @return array|mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_filter((array) $this, function ($attrValue, $attrName) {
            return in_array($attrName, $this->attributes);
        }, ARRAY_FILTER_USE_BOTH);
    }
}

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

namespace PrestaShop\Module\PsAccounts\Http\Resource;

use DateTime;
use PrestaShop\Module\PsAccounts\Type\Dto;

abstract class Resource extends Dto
{
    /**
     * @var bool
     */
    protected $throwOnUnexpectedProperties = false;

    /**
     * @param array $values
     * @param string $className
     * @param array $fields
     *
     * @return void
     */
    protected function castChildResource(array & $values, $className, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($values[$field]) && is_array($values[$field])) {
                $values[$field] = new $className($values[$field]);
            }
        }
    }

    /**
     * @param array $values
     * @param string $className
     * @param array $fields
     * @param bool $all
     *
     * @return void
     */
    protected function uncastChildResource(array & $values, $className, array $fields, $all = true)
    {
        foreach ($fields as $field) {
            if (isset($values[$field]) && is_a($values[$field], Resource::class, true)) {
                $values[$field] = $this->$field->toArray($all);
            }
        }
    }

    /**
     * @param array $values
     * @param array $fields
     *
     * @return void
     */
    protected function castBool(array & $values, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($values[$field])) {
                $values[$field] = (bool) $values[$field];
            }
        }
    }

    /**
     * @param array $values
     * @param array $fields
     *
     * @return void
     */
    protected function castDateTime(array & $values, array $fields)
    {
        foreach ($fields as $field) {
            if (!empty($values[$field])) {
                $values[$field] = new Datetime($values[$field]);
            }
        }
    }

    /**
     * @param array $values
     * @param array $fields
     * @param string $format
     *
     * @return void
     */
    protected function uncastDateTime(array & $values, array $fields, $format = DateTime::ATOM)
    {
        foreach ($fields as $field) {
            if (!empty($values[$field]) && $values[$field] instanceof DateTime) {
                $values[$field] = $values[$field]->format($format);
            }
        }
    }
}

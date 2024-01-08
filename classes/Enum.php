<?php

namespace PrestaShop\Module\PsAccounts;

use ReflectionClass;

abstract class Enum
{
    /**
     * @return array
     */
    public static function cases()
    {
        return (new ReflectionClass(static::class))->getConstants();
    }

    /**
     * @return array
     */
    public static function values()
    {
        return array_values(static::cases());
    }

    /**
     * @param mixed $value
     * @param bool $strict
     *
     * @return bool
     */
    public static function includes($value, $strict = false)
    {
        return in_array($value, array_values(static::cases()), $strict);
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Factory;

class Link
{
    /**
     * @return \Link
     */
    public static function get()
    {
        return \Context::getContext()->link;
    }
}

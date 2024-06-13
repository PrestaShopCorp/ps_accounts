<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Entity;

class NullToken extends \Lcobucci\JWT\Token
{
    public function toString(): string
    {
        return '';
    }
}

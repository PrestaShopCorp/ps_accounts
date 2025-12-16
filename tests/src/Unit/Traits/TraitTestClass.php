<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Traits;

use PrestaShop\Module\PsAccounts\Traits\WithPropertyTrait;

class TraitTestClass
{
    use WithPropertyTrait;

    /**
     * @var string
     */
    protected $property1;

    /**
     * @var string
     */
    protected $property2;

    public function __construct()
    {
        $this->initDefaults();
    }

    public function getDefaults()
    {
        return [
            'property1' => 'default value 1',
            'property2' => 'default value 2',
        ];
    }
}

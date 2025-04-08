<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Http\Client;

use PrestaShop\Module\PsAccounts\Http\Client\ConfigObject;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

/**
 * @property string $prop1
 * @property string $prop2
 */
class TestObject extends ConfigObject
{
    const prop1 = 'prop1';
    const prop2 = 'prop2';

    protected $defaults = [
        'prop2' => 'value2',
    ];

    protected $required = [
        self::prop1,
    ];
}

class ConfigObjectTest extends TestCase
{
    public function set_up()
    {
        parent::set_up();
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldThrowExceptionIfInvalidPropertyIsPassed()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Trying to access undefined property : foo.');

        new TestObject([
            'foo' => 'bar',
        ]);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldThrowExceptionIfRequiredPropertyDoesNotExist()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required property : prop1.');

        new TestObject([]);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldInitPropertiesWithDefaults()
    {
        $object = new TestObject([
            'prop1' => 'foo',
        ]);

        $this->assertEquals('value2', $object->prop2);
        $this->assertEquals('foo', $object->prop1);
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Traits;

use PrestaShop\Module\PsAccounts\Tests\TestCase;

class WithPropertyTraitTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldSetAProperty()
    {
        $instance = new TraitTestClass();

        $value = $this->faker->word;

        $this->assertEquals($value, $instance->withProperty1($value)->getProperty1());
    }

    /**
     * @test
     */
    public function itShouldRestoreAPropertyAfterGet()
    {
        $instance = new TraitTestClass();

        $value = $this->faker->word;

        $this->assertEquals($value, $instance->withProperty1($value)->getProperty1());

        $defaults = $instance->getDefaults();

        $this->assertEquals($defaults['property1'], $instance->getProperty1());
    }

    /**
     * @test
     */
    public function itShouldNotRestoreAPropertyAfterGet()
    {
        $instance = new TraitTestClass();

        $value = $this->faker->word;

        $this->assertEquals($value, $instance->withProperty1($value)->getProperty1(false));
        $this->assertEquals($value, $instance->getProperty1(false));
    }

    /**
     * @test
     */
    public function itShouldThrowExceptionIfPropertyDoesNotExist()
    {
        $instance = new TraitTestClass();

        $this->expectException(\InvalidArgumentException::class);

        $instance->getFooBar();
    }
}

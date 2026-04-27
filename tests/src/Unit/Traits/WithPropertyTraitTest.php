<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Traits;

use PrestaShop\Module\PsAccounts\Tests\TestCase;

class WithPropertyTraitTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldGetAProperty()
    {
        $instance = new TraitTestClass();

        $value = $this->faker->word;

        $this->assertEquals($value, $instance->withProperty('property1', $value)->getProperty('property1'));
    }

    /**
     * @test
     */
    public function itShouldGetAMagicProperty()
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

        $this->assertEquals($value, $instance->withProperty('property1', $value)->getProperty('property1'));

        $defaults = $instance->getDefaults();

        $this->assertEquals($defaults['property1'], $instance->getProperty('property1'));
    }

    /**
     * @test
     */
    public function itShouldRestoreAPropertyAfterMagicGet()
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
    public function itShouldNotRestoreAPropertyAfterMagicGet()
    {
        $instance = new TraitTestClass();

        $value = $this->faker->word;

        $this->assertEquals($value, $instance->withProperty('property1', $value)->getProperty('property1', false));
        $this->assertEquals($value, $instance->getProperty('property1', false));
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

        $handleException = function ($e) {
             $this->assertTrue((bool) preg_match('/Undefined property/', $e->getMessage()));
        };

        try {
            $instance->getProperty('fooBar');
        } catch (\Exception $e) {
            $handleException($e);
        } catch (\Throwable $e) {
            $handleException($e);
        }
    }

    /**
     * @test
     */
    public function itShouldThrowExceptionIfMagicPropertyDoesNotExist()
    {
        $instance = new TraitTestClass();

        $handleException = function ($e) {
            $this->assertTrue((bool) preg_match('/Undefined property/', $e->getMessage()));
        };

        try {
            $instance->getFooBar();
        } catch (\Exception $e) {
            $handleException($e);
        } catch (\Throwable $e) {
            $handleException($e);
        }
    }

    /**
     * @test
     */
    public function itShouldRestoreAProperty()
    {
        $instance = new TraitTestClass();

        $value = $this->faker->word;

        $instance->withProperty1($value);

        $this->assertEquals($value, $instance->getProperty1(false));

        $this->assertEquals($value, $instance->getProperty1(false));

        $instance->resetProperty1();

        $defaults = $instance->getDefaults();

        $this->assertEquals($defaults['property1'], $instance->getProperty1());
    }
}

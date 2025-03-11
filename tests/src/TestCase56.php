<?php

namespace PrestaShop\Module\PsAccounts\Tests;

class TestCase56 extends BaseTestCase
{
    protected function setUp()
    {
        $this->set_up();
    }

    protected function tearDown()
    {
        $this->tear_down();
    }

    /**
     * @param mixed $actual
     * @param string $message
     *
     * @return void
     */
    public static function assertIsBool($actual, $message = '')
    {
        self::assertInternalType('bool', $actual, $message);
    }

    /**
     * @param mixed $actual
     * @param string $message
     *
     * @return void
     */
    public static function assertIsArray($actual, $message = '')
    {
        self::assertInternalType('array', $actual, $message);
    }

    /**
     * @param mixed $actual
     * @param string $message
     *
     * @return void
     */
    public static function assertIsInt($actual, $message = '')
    {
        self::assertInternalType('int', $actual, $message);
    }

    /**
     * @param mixed $actual
     * @param string $message
     *
     * @return void
     */
    public static function assertIsString($actual, $message = '')
    {
        self::assertInternalType('string', $actual, $message);
    }
}

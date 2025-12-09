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

    /**
     * @param string $needle
     * @param string $haystack
     * @param string $message
     *
     * @return void
     */
    public static function assertStringContainsString($needle, $haystack, $message = '')
    {
        if (method_exists(\PHPUnit\Framework\Assert::class, 'assertStringContainsString')) {
            \PHPUnit\Framework\Assert::assertStringContainsString($needle, $haystack, $message);
        } else {
            // Fallback for PHPUnit < 7.5: use strpos to check if needle is in haystack
            if ($message === '') {
                $message = sprintf('Failed asserting that \'%s\' contains \'%s\'.', $haystack, $needle);
            }
            self::assertNotFalse(strpos($haystack, $needle), $message);
        }
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Tests;

class TestCase71 extends BaseTestCase
{
    use \DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

    protected function setUp(): void
    {
        $this->set_up();
    }

    protected function tearDown(): void
    {
        $this->tear_down();
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

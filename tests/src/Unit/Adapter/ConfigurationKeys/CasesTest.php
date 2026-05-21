<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Adapter\ConfigurationKeys;

use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class CasesTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldOnlyReturnScalarKeyConstants()
    {
        $cases = ConfigurationKeys::cases();

        foreach ($cases as $name => $value) {
            $this->assertIsString($value, sprintf('case "%s" should be a string, got %s', $name, gettype($value)));
        }
    }

    /**
     * @test
     */
    public function itShouldExcludeTheTokenKeysMetadataArrayFromCases()
    {
        $cases = ConfigurationKeys::cases();

        $this->assertArrayNotHasKey('TOKEN_KEYS', $cases);
    }

    /**
     * @test
     */
    public function itShouldStillExposeTokenKeysAsAClassConstant()
    {
        $this->assertIsArray(ConfigurationKeys::TOKEN_KEYS);
        $this->assertNotEmpty(ConfigurationKeys::TOKEN_KEYS);
        $this->assertContains(ConfigurationKeys::PS_ACCOUNTS_ACCESS_TOKEN, ConfigurationKeys::TOKEN_KEYS);
    }
}

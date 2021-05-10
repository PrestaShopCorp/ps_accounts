<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\ShopKeysService;

use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class CreatePairTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldGenerateKeyPair()
    {
        /** @var RsaKeysProvider $service */
        $service = $this->module->getService(RsaKeysProvider::class);

        $key = $service->createPair();
        $this->assertArrayHasKey('privatekey', $key, "Key 'privatekey' don't exist in Array");
        $this->assertArrayHasKey('publickey', $key, "Key 'publickey' don't exist in Array");
        $this->assertEquals('string', gettype($key['privatekey']), "'privatekey' isn't string");
        $this->assertEquals('string', gettype($key['publickey']), "'privatekey' isn't string");
    }
}

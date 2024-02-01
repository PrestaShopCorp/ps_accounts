<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Provider\RsaKeysProvider;

use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class CreatePairTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldCreateKeyPair()
    {
        /** @var RsaKeysProvider $publicKey */
        $publicKey = $this->module->getService(RsaKeysProvider::class);

        $key = $publicKey->createPair();

        $this->assertArrayHasKey('privatekey', $key);
        $this->assertArrayHasKey('publickey', $key);

        $this->assertEquals('string', gettype($key['privatekey']));
        $this->assertEquals('string', gettype($key['publickey']));
    }
}

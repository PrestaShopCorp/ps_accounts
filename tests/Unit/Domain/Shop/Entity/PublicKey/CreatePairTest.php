<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Domain\Shop\Entity\PublicKey;

use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\PublicKey;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class CreatePairTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldCreateKeyPair()
    {
        /** @var PublicKey $publicKey */
        $publicKey = $this->module->getService(PublicKey::class);

        $key = $publicKey->createPair();

        $this->assertArrayHasKey('privatekey', $key);
        $this->assertArrayHasKey('publickey', $key);

        $this->assertEquals('string', gettype($key['privatekey']));
        $this->assertEquals('string', gettype($key['publickey']));
    }
}

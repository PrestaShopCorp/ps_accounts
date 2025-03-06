<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Provider\RsaKeysProvider;

use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class CreatePairTest extends TestCase
{
    /**
     * @inject
     *
     * @var RsaKeysProvider
     */
    protected $rsaKeyService;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldCreateKeyPair()
    {
        $key = $this->rsaKeyService->createPair();

        $this->assertArrayHasKey('privatekey', $key);
        $this->assertArrayHasKey('publickey', $key);

        $this->assertEquals('string', gettype($key['privatekey']));
        $this->assertEquals('string', gettype($key['publickey']));
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Provider\RsaKeysProvider;

use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class VerifySignatureTest extends TestCase
{
    /**
     * @inject
     *
     * @var RsaKeysProvider
     */
    protected $rsaKeysProvider;

    /**
     * @test
     */
    public function itShouldVerifySignature()
    {
        $key = $this->rsaKeysProvider->createPair();

        $data = 'data-to-sign';

        $signature = $this->rsaKeysProvider->signData($key['privatekey'], $data);

        $this->assertEquals(1, $this->rsaKeysProvider->verifySignature($key['publickey'], $signature, $data));
    }
}

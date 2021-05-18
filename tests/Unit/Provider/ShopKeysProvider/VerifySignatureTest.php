<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Provider\ShopKeysProvider;

use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class VerifySignatureTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldVerifySignature()
    {
        /** @var RsaKeysProvider $service */
        $service = $this->module->getService(RsaKeysProvider::class);

        $key = $service->createPair();

        $data = 'data-to-sign';

        $signature = $service->signData($key['privatekey'], $data);

        $this->assertEquals(1, $service->verifySignature($key['publickey'], $signature, $data));
    }
}

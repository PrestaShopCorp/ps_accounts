<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Provider\RsaKeysProvider;

use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\PublicKey;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class VerifySignatureTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldVerifySignature()
    {
        /** @var PublicKey $service */
        $service = $this->module->getService(PublicKey::class);

        $key = $service->createPair();

        $data = 'data-to-sign';

        $signature = $service->signData($key['privatekey'], $data);

        $this->assertEquals(1, $service->verifySignature($key['publickey'], $signature, $data));
    }
}

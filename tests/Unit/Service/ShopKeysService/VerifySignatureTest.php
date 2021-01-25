<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\ShopKeysService;

use PrestaShop\Module\PsAccounts\Service\ShopKeysService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class VerifySignatureTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldVerifySignature()
    {
        /** @var ShopKeysService $service */
        $service = $this->module->getService(ShopKeysService::class);

        $key = $service->createPair();

        $data = 'data-to-sign';

        $signature = $service->signData($key['privatekey'], $data);

        $this->assertEquals(1, $service->verifySignature($key['publickey'], $signature, $data));
    }
}

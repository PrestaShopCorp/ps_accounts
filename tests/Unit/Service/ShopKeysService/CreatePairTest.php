<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\ShopKeysService;

use Db;
use Module;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\ShopKeysService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use Ps_accounts;

class CreatePairTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_generate_key_pair()
    {
        /** @var ShopKeysService $service */
        $service = $this->module->getService(ShopKeysService::class);

        $key = $service->createPair();
        $this->assertArrayHasKey('privatekey', $key, "Key 'privatekey' don't exist in Array");
        $this->assertArrayHasKey('publickey', $key, "Key 'publickey' don't exist in Array");
        $this->assertEquals('string', gettype($key['privatekey']), "'privatekey' isn't string");
        $this->assertEquals('string', gettype($key['publickey']), "'privatekey' isn't string");
    }
}

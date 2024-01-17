<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Provider\RsaKeysProvider;

use Db;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GenerateKeysTest extends TestCase
{
    /**
     * @test
     *
     * @throws SshKeysNotFoundException
     */
    public function itShouldCreateRsaKeys()
    {
        /** @var RsaKeysProvider $service */
        $service = $this->module->getService(RsaKeysProvider::class);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        //echo "A\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        // Empty DB
        $configuration->updateAccountsRsaPrivateKey(null);
        $configuration->updateAccountsRsaPublicKey(null);

        $this->assertEmpty($configuration->getAccountsRsaPrivateKey());
        $this->assertEmpty($configuration->getAccountsRsaPublicKey());

        $service->generateKeys();

        //echo "B\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        $this->assertNotEmpty($configuration->getAccountsRsaPrivateKey());
        $this->assertNotEmpty($configuration->getAccountsRsaPublicKey());

        $data = $this->faker->sentence();
        $signedData = $service->signData($configuration->getAccountsRsaPrivateKey(), $data);

        $this->assertTrue(
            $service->verifySignature(
                $configuration->getAccountsRsaPublicKey(),
                $signedData,
                $data
            )
        );
    }

    /**
     * @test
     */
    public function itShouldGenerateKeyPair()
    {
        /** @var RsaKeysProvider $service */
        $service = $this->module->getService(RsaKeysProvider::class);

        $key = $service->createPair();

        $this->assertArrayHasKey('privatekey', $key);
        $this->assertArrayHasKey('publickey', $key);

        $this->assertEquals('string', gettype($key['privatekey']));
        $this->assertEquals('string', gettype($key['publickey']));
    }
}

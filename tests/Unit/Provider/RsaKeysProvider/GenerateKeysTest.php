<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Provider\RsaKeysProvider;

use Db;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\PublicKey;
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
        /** @var \PrestaShop\Module\PsAccounts\Domain\Shop\Entity\PublicKey $service */
        $service = $this->module->getService(PublicKey::class);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        //echo "A\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        // Empty DB
        $configuration->updateAccountsRsaPrivateKey(null);
        $configuration->updateAccountsRsaPublicKey(null);
        $configuration->updateAccountsRsaSignData(null);

        $this->assertEmpty($configuration->getAccountsRsaPrivateKey());
        $this->assertEmpty($configuration->getAccountsRsaPublicKey());
        $this->assertEmpty($configuration->getAccountsRsaSignData());

        $service->generateKeys();

        //echo "B\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        $this->assertNotEmpty($configuration->getAccountsRsaPrivateKey());
        $this->assertNotEmpty($configuration->getAccountsRsaPublicKey());
        $this->assertNotEmpty($configuration->getAccountsRsaSignData());

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
        /** @var \PrestaShop\Module\PsAccounts\Domain\Shop\Entity\PublicKey $service */
        $service = $this->module->getService(PublicKey::class);

        $key = $service->createPair();

        $this->assertArrayHasKey('privatekey', $key);
        $this->assertArrayHasKey('publickey', $key);

        $this->assertEquals('string', gettype($key['privatekey']));
        $this->assertEquals('string', gettype($key['publickey']));
    }
}

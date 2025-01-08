<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Provider\RsaKeysProvider;

use PrestaShop\Module\PsAccounts\Account\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GenerateKeysTest extends TestCase
{
    /**
     * @inject
     *
     * @var RsaKeysProvider
     */
    protected $rsaKeysProvider;

    /**
     * @test
     *
     * @return void
     *
     * @throws SshKeysNotFoundException
     */
    public function itShouldGenerateKeys()
    {
        //echo "A\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        // Empty DB
        $this->rsaKeysProvider->cleanupKeys();

        $this->assertEmpty($this->rsaKeysProvider->getPrivateKey());
        $this->assertEmpty($this->rsaKeysProvider->getPublicKey());

        $this->rsaKeysProvider->generateKeys();

        //echo "B\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        $this->assertNotEmpty($this->rsaKeysProvider->getPrivateKey());
        $this->assertNotEmpty($this->rsaKeysProvider->getPublicKey());

        $data = $this->faker->sentence();
        $signedData = $this->rsaKeysProvider->signData($this->rsaKeysProvider->getPrivateKey(), $data);

        $this->assertTrue(
            $this->rsaKeysProvider->verifySignature(
                $this->rsaKeysProvider->getPublicKey(),
                $signedData,
                $data
            )
        );
    }
}

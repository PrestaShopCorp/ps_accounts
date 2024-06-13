<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Domain\Shop\Entity\PublicKey;

use Db;
use PrestaShop\Module\PsAccounts\Domain\Shop\Exception\PublicKeyException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\PublicKey;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GenerateKeysTest extends TestCase
{
    /**
     * @test
     *
     * @throws \PrestaShop\Module\PsAccounts\Domain\Shop\Exception\PublicKeyException
     */
    public function itShouldGenerateKeys()
    {
        /** @var PublicKey $publicKey */
        $publicKey = $this->module->getService(PublicKey::class);

        /** @var ConfigurationRepository $configRepository */
        $configRepository = $this->module->getService(ConfigurationRepository::class);

        //echo "A\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        // Empty DB
        $configRepository->updateAccountsRsaPrivateKey(null);
        $configRepository->updateAccountsRsaPublicKey(null);

        $this->assertEmpty($configRepository->getAccountsRsaPrivateKey());
        $this->assertEmpty($configRepository->getAccountsRsaPublicKey());

        $publicKey->generateKeys();

        //echo "B\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        $this->assertNotEmpty($configRepository->getAccountsRsaPrivateKey());
        $this->assertNotEmpty($configRepository->getAccountsRsaPublicKey());
    }
}

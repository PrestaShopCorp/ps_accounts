<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use Db;
use Module;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\SshKey;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use Ps_accounts;

class GenerateSshKeyTest extends TestCase
{
    /**
     * @test
     *
     * @throws SshKeysNotFoundException
     */
    public function it_should_update_ssh_keys()
    {
        /** @var Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');

        /** @var PsAccountsService $service */
        $service = $module->getService(PsAccountsService::class);

        /** @var ConfigurationRepository $configuration */
        $configuration = $module->getService(ConfigurationRepository::class);

        //echo "A\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        // Empty DB
        $configuration->updateAccountsRsaPrivateKey(null);
        $configuration->updateAccountsRsaPublicKey(null);
        $configuration->updateAccountsRsaSignData(null);

        $this->assertEmpty($configuration->getAccountsRsaPrivateKey());
        $this->assertEmpty($configuration->getAccountsRsaPublicKey());
        $this->assertEmpty($configuration->getAccountsRsaSignData());

        $service->generateSshKey();

        //echo "B\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        $this->assertNotEmpty($configuration->getAccountsRsaPrivateKey());
        $this->assertNotEmpty($configuration->getAccountsRsaPublicKey());
        $this->assertNotEmpty($configuration->getAccountsRsaSignData());

        $sshKey = new SshKey();
        $data = $this->faker->sentence();
        $signedData = $sshKey->signData($configuration->getAccountsRsaPrivateKey(), $data);

        $this->assertTrue(
            $sshKey->verifySignature(
                $configuration->getAccountsRsaPublicKey(),
                $signedData,
                $data
            )
        );
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsAccountLinkedTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
        /** @var ShopTokenRepository $repos */
        $repos = $this->module->getService(ShopTokenRepository::class);
        $repos->updateCredentials(
            (string) $this->makeFirebaseToken(null, ['email_verified' => true]),
            base64_encode($this->faker->name)
        );

        /** @var UserTokenRepository $tokenRepos */
        $repos = $this->module->getService(UserTokenRepository::class);
        $repos->updateCredentials(
            (string) $this->makeFirebaseToken(null, ['email_verified' => true]),
            base64_encode($this->faker->name)
        );

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertTrue($service->isAccountLinked());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        /** @var ShopTokenRepository $repos */
        $repos = $this->module->getService(ShopTokenRepository::class);
        $repos->updateCredentials(
            (string) $this->makeFirebaseToken(null, ['email_verified' => true]),
            base64_encode($this->faker->name)
        );

        /** @var UserTokenRepository $tokenRepos */
        $repos = $this->module->getService(UserTokenRepository::class);
        $repos->cleanupCredentials();

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertFalse($service->isAccountLinked());
    }
}

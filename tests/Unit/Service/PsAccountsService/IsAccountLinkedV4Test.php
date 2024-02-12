<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsAccountLinkedV4Test extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
        $token = $this->makeFirebaseToken(null, ['email_verified' => true]);

        /** @var ShopTokenRepository $tokenRepos */
        $tokenRepos = $this->module->getService(ShopTokenRepository::class);
        $tokenRepos->updateCredentials((string) $token, base64_encode($this->faker->name));

        $this->configurationRepository->updateUserFirebaseIdToken('');
        $this->configurationRepository->updateFirebaseEmail($this->faker->safeEmail);

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertTrue($service->isAccountLinkedV4());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        $token = $this->makeFirebaseToken(null, ['email_verified' => true]);

        /** @var ShopTokenRepository $tokenRepos */
        $tokenRepos = $this->module->getService(ShopTokenRepository::class);
        $tokenRepos->updateCredentials((string) $token, base64_encode($this->faker->name));

        $this->configurationRepository->updateFirebaseEmail('');

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertFalse($service->isAccountLinkedV4());
    }
}

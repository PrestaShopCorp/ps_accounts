<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Repository\Support\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsEmailValidatedTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
        /** @var UserTokenRepository $tokenRepos */
        $tokenRepos = $this->module->getService(UserTokenRepository::class);

        $token = $this->makeFirebaseToken(null, ['email_verified' => true]);

        $tokenRepos->updateCredentials($token, base64_encode($this->faker->name));

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertTrue($service->isEmailValidated());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        /** @var UserTokenRepository $tokenRepos */
        $tokenRepos = $this->module->getService(UserTokenRepository::class);

        $tokenRepos->updateCredentials(
            $this->makeFirebaseToken(null, ['email_verified' => false]),
            null //base64_encode($this->faker->name)
        );

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertFalse($service->isEmailValidated());
    }
}

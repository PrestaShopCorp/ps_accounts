<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

<<<<<<< HEAD
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
=======
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
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
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

<<<<<<< HEAD
        /** @var ShopTokenRepository $tokenRepos */
        $tokenRepos = $this->module->getService(ShopTokenRepository::class);
        $tokenRepos->updateCredentials((string) $token, base64_encode($this->faker->name));
=======
        /** @var ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);
        $shopSession->setToken($token, $refreshToken);

        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);
        $ownerSession->cleanup();
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

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
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

<<<<<<< HEAD
        /** @var ShopTokenRepository $tokenRepos */
        $tokenRepos = $this->module->getService(ShopTokenRepository::class);
        $tokenRepos->updateCredentials((string) $token, base64_encode($this->faker->name));
=======
        /** @var ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);
        $shopSession->setToken($token, $refreshToken);
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

        $this->configurationRepository->updateFirebaseEmail('');

        /** @var PsAccountsService $service */
        $service = $this->module->getService(PsAccountsService::class);

        $this->assertFalse($service->isAccountLinkedV4());
    }
}

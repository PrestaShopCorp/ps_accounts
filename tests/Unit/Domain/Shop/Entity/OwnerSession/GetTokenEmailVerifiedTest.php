<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Domain\Shop\Entity\OwnerSession;

<<<<<<< HEAD:tests/Unit/Repository/UserTokenRepository/GetTokenEmailVerifiedTest.php
=======
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):tests/Unit/Domain/Shop/Entity/OwnerSession/GetTokenEmailVerifiedTest.php
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetTokenEmailVerifiedTest extends TestCase
{
    /**
     * @inject
     *
     * @var UserTokenRepository
     */
    protected $repository;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
            'email_verified' => true,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

<<<<<<< HEAD:tests/Unit/Repository/UserTokenRepository/GetTokenEmailVerifiedTest.php
        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertTrue($this->repository->getTokenEmailVerified());
=======
        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertTrue($ownerSession->isEmailVerified());
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):tests/Unit/Domain/Shop/Entity/OwnerSession/GetTokenEmailVerifiedTest.php
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
            'email_verified' => false,
        ]);

        $refreshToken = null; //$this->makeJwtToken(new \DateTimeImmutable('+1 year'));

<<<<<<< HEAD:tests/Unit/Repository/UserTokenRepository/GetTokenEmailVerifiedTest.php
        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertFalse($this->repository->getTokenEmailVerified());
=======
        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertFalse($ownerSession->isEmailVerified());
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):tests/Unit/Domain/Shop/Entity/OwnerSession/GetTokenEmailVerifiedTest.php
    }
}

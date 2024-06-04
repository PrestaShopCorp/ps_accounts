<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Domain\Shop\Entity\OwnerSession;

<<<<<<< HEAD:tests/Unit/Repository/UserTokenRepository/IsTokenExpiredTest.php
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
=======
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):tests/Unit/Domain/Shop/Entity/OwnerSession/IsTokenExpiredTest.php
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsTokenExpiredTest extends TestCase
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
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

<<<<<<< HEAD:tests/Unit/Repository/UserTokenRepository/IsTokenExpiredTest.php
        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertTrue($this->repository->isTokenExpired());
=======
        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertTrue($ownerSession->getToken()->isExpired());
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):tests/Unit/Domain/Shop/Entity/OwnerSession/IsTokenExpiredTest.php
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('+2 hours'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

<<<<<<< HEAD:tests/Unit/Repository/UserTokenRepository/IsTokenExpiredTest.php
        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertFalse($this->repository->isTokenExpired());
=======
        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertFalse($ownerSession->getToken()->isExpired());
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):tests/Unit/Domain/Shop/Entity/OwnerSession/IsTokenExpiredTest.php
    }
}

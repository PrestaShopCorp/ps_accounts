<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Domain\Shop\Entity\ShopSession;

use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsTokenExpiredTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopTokenRepository
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
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

<<<<<<< HEAD:tests/Unit/Repository/ShopTokenRepository/IsTokenExpiredTest.php
        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertTrue($this->repository->isTokenExpired());
=======
        /** @var ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);

        $shopSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertTrue($shopSession->getToken()->isExpired());
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):tests/Unit/Domain/Shop/Entity/ShopSession/IsTokenExpiredTest.php
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
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

<<<<<<< HEAD:tests/Unit/Repository/ShopTokenRepository/IsTokenExpiredTest.php
        $this->repository->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertFalse($this->repository->isTokenExpired());
=======
        /** @var ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);

        $shopSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertFalse($shopSession->getToken()->isExpired());
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):tests/Unit/Domain/Shop/Entity/ShopSession/IsTokenExpiredTest.php
    }
}

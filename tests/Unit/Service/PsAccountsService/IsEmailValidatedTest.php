<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

<<<<<<< HEAD
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
=======
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IsEmailValidatedTest extends TestCase
{
    /**
     * @inject
     *
     * @var OwnerSession
     */
    protected $ownerSession;

    /**
     * @inject
     *
     * @var PsAccountsService
     */
    protected $service;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnTrue()
    {
<<<<<<< HEAD
=======
        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);

>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
        $token = $this->makeFirebaseToken(null, ['email_verified' => true]);
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

<<<<<<< HEAD
        $this->ownerSession->setToken($token);
=======
        $ownerSession->setToken($token, $refreshToken);
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

        $this->assertTrue($this->service->isEmailValidated());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnFalse()
    {
<<<<<<< HEAD
        $this->ownerSession->setToken(
            $this->makeFirebaseToken(null, ['email_verified' => false])
=======
        /** @var OwnerSession $ownerSession */
        $ownerSession = $this->module->getService(OwnerSession::class);

        $ownerSession->setToken(
            $this->makeFirebaseToken(null, ['email_verified' => false]),
            '' //$this->makeJwtToken(new \DateTimeImmutable('+1 year'))
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
        );

        $this->assertFalse($this->service->isEmailValidated());
    }
}

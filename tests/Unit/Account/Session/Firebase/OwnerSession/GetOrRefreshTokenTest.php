<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\Firebase\OwnerSession;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\SessionHelpers;

class GetOrRefreshTokenTest extends TestCase
{
    use SessionHelpers;

    /**
     * @inject
     *
     * @var Firebase\OwnerSession
     */
    protected $ownerSession;

    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopProvider;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnValidToken()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $this->ownerSession->setToken((string) $idToken);

        $this->assertEquals((string) $idToken, (string) $this->ownerSession->getOrRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldRefreshExpiredToken()
    {
        $expired = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $refreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $shopSession = $this->getMockedShopSession($this->createApiResponse([
            'userToken' => (string) $refreshed,
            'shopToken' => (string) $refreshed,
        ], 200, true));

        $session = new Firebase\OwnerSession($this->configurationRepository, $shopSession);

        //$shopSession->setToken((string) $expired);
        $session->setToken((string) $expired);

        $this->assertEquals((string) $expired, (string) $session->getToken());
        $this->assertEquals((string) $refreshed, (string) $session->getOrRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldNotRefreshValidToken()
    {
        $refreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $shopSession = $this->getMockedShopSession($this->createApiResponse([
            'userToken' => (string) $refreshed,
            'shopToken' => (string) $refreshed,
        ], 200, true));

        $session = new Firebase\OwnerSession($this->configurationRepository, $shopSession);

        //$shopSession->setToken((string) $expired);
        $session->setToken((string) $refreshed);

        $this->assertEquals((string) $refreshed, (string) $session->getToken());
        $this->assertEquals((string) $refreshed, (string) $session->getOrRefreshToken());
    }
}

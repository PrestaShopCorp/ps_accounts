<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\Firebase\OwnerSession;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetOrRefreshTokenTest extends TestCase
{
    use \PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\SessionHelpers;

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
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('+1 hours'), [
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
        $expiredToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $userRefreshedToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $shopRefreshedToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $userRefreshToken = $this->faker->regexify('[a-zA-Z\d]{40}');
        $shopRefreshToken = $this->faker->regexify('[a-zA-Z\d]{40}');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createApiResponse([
                'userToken' => (string) $userRefreshedToken,
                'userRefreshToken' => $userRefreshToken,
                'shopToken' => (string) $shopRefreshedToken,
                'shopRefreshToken' => $shopRefreshToken,
            ], 200, true),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable())))
        );

        $session->setToken((string) $expiredToken, (string) $refreshToken);

        $this->assertEquals((string) $expiredToken, (string) $session->getToken());
        $this->assertEquals((string) $userRefreshedToken, (string) $session->getOrRefreshToken());
        $this->assertEquals($userRefreshToken, $session->getToken()->getRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldNotRefreshValidToken()
    {
        $notExpired = $this->makeJwtToken(new \DateTimeImmutable('+1 hour'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $notExpiredRefresh = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $userRefreshedToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $shopRefreshedToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $userRefreshToken = $this->faker->regexify('[a-zA-Z\d]{40}');
        $shopRefreshToken = $this->faker->regexify('[a-zA-Z\d]{40}');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createApiResponse([
                'userToken' => (string) $userRefreshedToken,
                'userRefreshToken' => $userRefreshToken,
                'shopToken' => (string) $shopRefreshedToken,
                'shopRefreshToken' => $shopRefreshToken,
            ], 200, true),
            $this->getMockedShopSession(new Token('')) // Empty token
        );

        //$shopSession->setToken((string) $expired);
        $session->setToken((string) $notExpired, (string) $notExpiredRefresh);

        $this->assertEquals((string) $notExpired, (string) $session->getToken());
        $this->assertEquals((string) $notExpired, (string) $session->getOrRefreshToken());
        $this->assertEquals($notExpiredRefresh, $session->getToken()->getRefreshToken());
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\Firebase\OwnerSession;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetValidTokenTest extends TestCase
{
    use \PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\SessionHelpers;

    /**
     * @inject
     *
     * @var Firebase\OwnerSession
     */
    protected $session;

    /**
     * @inject
     *
     * @var OAuth2Service
     */
    protected $oAuth2Service;

    /**
     * @test
     */
    public function itShouldReturnValidToken()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $idToken = $this->makeUserToken($pointOfContactUuid, '+1 hours');

        $this->statusManager->setPointOfContactUuid($pointOfContactUuid);
        $this->session->setToken((string) $idToken);

        $this->assertEquals((string) $idToken, (string) $this->session->getValidToken());
        $this->assertEquals((string) $idToken, (string) $this->session->getOrRefreshToken());
    }

    /**
     * @test
     */
    public function itShouldRefreshExpiredToken()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $expiredToken = $this->makeUserToken($pointOfContactUuid, 'yesterday');
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));
        $userRefreshedToken = $this->makeUserToken($pointOfContactUuid, 'tomorrow');
        $userRefreshToken = $this->faker->regexify('[a-zA-Z\d]{40}');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($userRefreshedToken, $userRefreshToken),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $session->setToken((string) $expiredToken, (string) $refreshToken);

        $this->assertEquals((string) $expiredToken, (string) $session->getToken());
        $this->assertEquals((string) $userRefreshedToken, (string) $session->getValidToken());
        $this->assertEquals((string) $userRefreshedToken, (string) $session->getOrRefreshToken());
        $this->assertEquals($userRefreshToken, $session->getToken()->getRefreshToken());
        $this->assertEquals(1, $this->countApiCalls('tokens'));
        $this->assertEquals(0, $this->countApiCalls('status'));
    }

    /**
     * @test
     */
    public function itShouldNotRefreshValidToken()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $notExpired = $this->makeUserToken($pointOfContactUuid, '+1 hour');
        $notExpiredRefresh = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($pointOfContactUuid, 'tomorrow')),
            $this->getMockedShopSession(new Token('')), // Empty token
            $pointOfContactUuid
        );

        $session->setToken((string) $notExpired, (string) $notExpiredRefresh);

        $this->assertEquals((string) $notExpired, (string) $session->getToken());
        $this->assertEquals((string) $notExpired, (string) $session->getValidToken());
        $this->assertEquals((string) $notExpired, (string) $session->getOrRefreshToken());
        $this->assertEquals($notExpiredRefresh, $session->getToken()->getRefreshToken());
        $this->assertEquals([], $this->apiCalls);
    }

    /**
     * @test
     */
    public function itShouldNotCallApiWithoutPointOfContact()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($this->faker->uuid, 'tomorrow')),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            null
        );

        $session->setToken(
            (string) $this->makeUserToken($this->faker->uuid, 'yesterday'),
            $this->faker->regexify('[a-zA-Z\d]{40}')
        );

        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals('', (string) $session->getValidToken(true));
        $this->assertEquals('', $session->getToken()->getRefreshToken());
        $this->assertFalse($session->isEmailVerified());
        $this->assertEquals([], $this->apiCalls);
    }

    /**
     * @test
     */
    public function itShouldCleanupResidualRefreshTokenWithoutPointOfContact()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            null
        );

        $session->setToken('', $this->faker->regexify('[a-zA-Z\d]{40}'));

        $session->getValidToken();

        $this->assertEquals('', $session->getToken()->getRefreshToken());
    }

    /**
     * @test
     */
    public function itShouldReplaceTokenOfPreviousPointOfContact()
    {
        $previousPointOfContactUuid = $this->faker->uuid;
        $pointOfContactUuid = $this->faker->uuid;
        $newToken = $this->makeUserToken($pointOfContactUuid, 'tomorrow');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($newToken),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid,
            $this->createStatusResponse($pointOfContactUuid)
        );

        $session->setToken((string) $this->makeUserToken($previousPointOfContactUuid, '+1 hour'));

        $this->assertEquals((string) $newToken, (string) $session->getValidToken());
        $this->assertEquals((string) $newToken, (string) $session->getValidToken());
        $this->assertEquals(1, $this->countApiCalls('status'));
        $this->assertEquals(1, $this->countApiCalls('tokens'));
    }

    /**
     * @test
     */
    public function itShouldKeepTokenWhenCachedStatusIsStale()
    {
        $previousPointOfContactUuid = $this->faker->uuid;
        $pointOfContactUuid = $this->faker->uuid;
        $token = $this->makeUserToken($pointOfContactUuid, '+1 hour');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($pointOfContactUuid, 'tomorrow')),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $previousPointOfContactUuid,
            $this->createStatusResponse($pointOfContactUuid)
        );

        $session->setToken((string) $token);

        $this->assertEquals((string) $token, (string) $session->getValidToken());
        $this->assertEquals((string) $token, (string) $session->getValidToken());
        $this->assertEquals(1, $this->countApiCalls('status'));
        $this->assertEquals(0, $this->countApiCalls('tokens'));
    }

    /**
     * @test
     */
    public function itShouldNotAskAgainWhenNoTokenIsReturnedForPointOfContact()
    {
        $pointOfContactUuid = $this->faker->uuid;

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $session->setToken(
            (string) $this->makeUserToken($pointOfContactUuid, 'yesterday'),
            $this->faker->regexify('[a-zA-Z\d]{40}')
        );

        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertFalse($session->isEmailVerified());
        $this->assertEquals('', $session->getToken()->getRefreshToken());
        $this->assertEquals(1, $this->countApiCalls('tokens'));
    }

    /**
     * @test
     */
    public function itShouldAskAgainWhenUnavailabilityDelayIsOver()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $userRefreshedToken = $this->makeUserToken($pointOfContactUuid, 'tomorrow');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($userRefreshedToken),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $this->configurationRepository->updateUserTokenUnavailable(json_encode([
            'pointOfContactUuid' => $pointOfContactUuid,
            'until' => time() - 1,
        ]));

        $this->assertEquals((string) $userRefreshedToken, (string) $session->getValidToken());
        $this->assertEquals(1, $this->countApiCalls('tokens'));
    }

    /**
     * @test
     */
    public function itShouldNotForceRefreshWhenEmailIsNotVerified()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $unverifiedToken = $this->makeUserToken($pointOfContactUuid, '+1 hour', ['email_verified' => false]);

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($pointOfContactUuid, 'tomorrow', ['email_verified' => true])),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $session->setToken((string) $unverifiedToken);

        $this->assertFalse($session->isEmailVerified());
        $this->assertFalse($session->isEmailVerified());
        $this->assertEquals((string) $unverifiedToken, (string) $session->getToken());
        $this->assertEquals([], $this->apiCalls);
    }

    /**
     * @test
     */
    public function itShouldNotCallApiWhenEmailIsVerifiedOnExpiredToken()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $expiredToken = $this->makeUserToken($pointOfContactUuid, 'yesterday', ['email_verified' => true]);

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createResponse(['message' => 'Error !'], 500),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $session->setToken((string) $expiredToken);

        $this->assertTrue($session->isEmailVerified());
        $this->assertEquals((string) $expiredToken, (string) $session->getToken());
        $this->assertEquals([], $this->apiCalls);
    }

    /**
     * @test
     */
    public function itShouldThrowRefreshTokenExceptionOnApiError()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $expired = $this->makeUserToken($pointOfContactUuid, 'yesterday');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createResponse([
                'message' => 'Error !',
            ], 403, false),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $session->setToken((string) $expired);

        $e = null;
        try {
            $session->getValidToken();
        } catch (RefreshTokenException $e) {
        }

        $this->assertInstanceOf(RefreshTokenException::class, $e);
        $this->assertEquals('', (string) $session->getToken());
        $this->assertInstanceOf(NullToken::class, $session->getToken()->getJwt());
    }

    /**
     * @param string $pointOfContactUuid
     * @param string $expiresAt
     * @param array $claims
     *
     * @return \PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token
     */
    private function makeUserToken($pointOfContactUuid, $expiresAt, array $claims = [])
    {
        return $this->makeJwtToken(new \DateTimeImmutable($expiresAt), array_merge([
            'sub' => $pointOfContactUuid,
            'email' => $this->faker->safeEmail,
        ], $claims));
    }

    /**
     * @param \PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token|null $userToken
     * @param string|null $userRefreshToken
     *
     * @return \PrestaShop\Module\PsAccounts\Http\Client\Response
     */
    private function createTokensResponse($userToken, $userRefreshToken = null)
    {
        return $this->createResponse([
            'shop' => [
                'token' => (string) $this->makeJwtToken(new \DateTimeImmutable('tomorrow')),
                'refreshToken' => $this->faker->regexify('[a-zA-Z\d]{40}'),
            ],
            // what accounts-api returns for a shop without point of contact
            'pointOfContact' => [
                'token' => $userToken ? (string) $userToken : null,
                'refreshToken' => $userToken ? ($userRefreshToken ?: $this->faker->regexify('[a-zA-Z\d]{40}')) : null,
            ],
        ], 200);
    }

    /**
     * @param string $pointOfContactUuid
     *
     * @return \PrestaShop\Module\PsAccounts\Http\Client\Response
     */
    private function createStatusResponse($pointOfContactUuid)
    {
        return $this->createResponse([
            'cloudShopId' => $this->faker->uuid,
            'isVerified' => true,
            'pointOfContactUuid' => $pointOfContactUuid,
            'pointOfContactEmail' => $this->faker->safeEmail,
        ], 200);
    }
}

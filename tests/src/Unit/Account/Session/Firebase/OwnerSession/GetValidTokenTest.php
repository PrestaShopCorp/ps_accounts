<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\Firebase\OwnerSession;

use PrestaShop\Module\PsAccounts\Account\CachedShopStatus;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
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

        $storedToken = $this->makeUserToken($this->faker->uuid, 'yesterday', ['email_verified' => true]);
        $session->setToken((string) $storedToken, $this->faker->regexify('[a-zA-Z\d]{40}'));

        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals('', (string) $session->getValidToken(true));
        $this->assertFalse($session->isEmailVerified());
        $this->assertEquals([], $this->apiCalls);
        // kept: the status may only be transiently empty
        $this->assertEquals((string) $storedToken, (string) $session->getToken());
    }

    /**
     * @test
     */
    public function itShouldReplaceTokenOfPreviousPointOfContact()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $newToken = $this->makeUserToken($pointOfContactUuid, 'tomorrow');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($newToken),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid,
            $this->createStatusResponse($pointOfContactUuid)
        );

        $session->setToken((string) $this->makeUserToken($this->faker->uuid, '+1 hour'));

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
        $pointOfContactUuid = $this->faker->uuid;
        $token = $this->makeUserToken($pointOfContactUuid, '+1 hour');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($pointOfContactUuid, 'tomorrow')),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $this->faker->uuid,
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
    public function itShouldKeepToCachedStatusWhenStatusCannotBeFetched()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $newToken = $this->makeUserToken($pointOfContactUuid, 'tomorrow');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($newToken),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $session->setToken((string) $this->makeUserToken($this->faker->uuid, '+1 hour'));

        $this->assertEquals((string) $newToken, (string) $session->getValidToken(false, false));
        $this->assertEquals((string) $newToken, (string) $session->getValidToken(false, false));
        $this->assertEquals((string) $newToken, (string) $session->getValidToken(false, false));
        $this->assertEquals(1, $this->countApiCalls('status'));
        $this->assertEquals(1, $this->countApiCalls('tokens'));
    }

    /**
     * @test
     */
    public function itShouldNotThrowWhenStatusCannotBeFetchedWithThrowExceptionEnabled()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $newToken = $this->makeUserToken($pointOfContactUuid, 'tomorrow');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($newToken),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $session->setToken((string) $this->makeUserToken($this->faker->uuid, '+1 hour'));
        $this->mockedStatusManager->withThrowException(true);

        $this->assertEquals((string) $newToken, (string) $session->getValidToken(false, false));
        $this->assertFalse($session->isEmailVerified());
    }

    /**
     * @test
     */
    public function itShouldNeitherServeNorKeepTokenReturnedForPreviousPointOfContact()
    {
        $pointOfContactUuid = $this->faker->uuid;

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($this->faker->uuid, 'tomorrow')),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid,
            $this->createStatusResponse($pointOfContactUuid)
        );

        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals('', (string) $session->getToken());
        // one token call per call, as without token on main; the status is read once per cache lifetime
        $this->assertEquals(2, $this->countApiCalls('tokens'));
        $this->assertEquals(1, $this->countApiCalls('status'));
    }

    /**
     * @test
     */
    public function itShouldCleanupTokenWhenNoPointOfContactIsReturned()
    {
        $pointOfContactUuid = $this->faker->uuid;

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid,
            $this->createStatusResponse(null)
        );

        $session->setToken(
            (string) $this->makeUserToken($pointOfContactUuid, 'yesterday'),
            $this->faker->regexify('[a-zA-Z\d]{40}')
        );

        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertFalse($session->isEmailVerified());
        $this->assertEquals('', (string) $session->getToken());
        $this->assertEquals('', $session->getToken()->getRefreshToken());
        $this->assertEquals(1, $this->countApiCalls('tokens'));
        $this->assertEquals(1, $this->countApiCalls('status'));
    }

    /**
     * @test
     */
    public function itShouldReadStatusOncePerCacheLifetimeWhenNoTokenIsReturnedForPointOfContact()
    {
        $pointOfContactUuid = $this->faker->uuid;

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid,
            $this->createStatusResponse($pointOfContactUuid)
        );

        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        // one token call per call, as on main, until accounts-api is consistent (ACC-3736)
        $this->assertEquals(3, $this->countApiCalls('tokens'));
        $this->assertEquals(1, $this->countApiCalls('status'));
    }

    /**
     * @test
     */
    public function itShouldNotRetryUnreachableStatusInRuntimeWhenTokenOfAnotherPointOfContactIsReturned()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($this->faker->uuid, 'tomorrow')),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $this->faker->uuid
        );

        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getToken());
        // one token call per call, as without token on main; one failed status call per runtime
        $this->assertEquals(3, $this->countApiCalls('tokens'));
        $this->assertEquals(1, $this->countApiCalls('status'));
    }

    /**
     * @test
     */
    public function itShouldNotRetryUnreachableStatusInRuntimeWhenNoTokenIsReturnedForPointOfContact()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $this->faker->uuid
        );

        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals(3, $this->countApiCalls('tokens'));
        $this->assertEquals(1, $this->countApiCalls('status'));
    }

    /**
     * @test
     */
    public function itShouldNotRetryUnreachableStatusInRuntimeWhenCachedStatusIsInvalidated()
    {
        $pointOfContactUuid = $this->faker->uuid;

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        // invalidated (e.g. after an identity verification) while not expired yet
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => false,
            'updatedAt' => date('Y-m-d H:i:s'),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->faker->uuid,
                'pointOfContactUuid' => $pointOfContactUuid,
            ]),
        ]))->toArray()));

        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals(3, $this->countApiCalls('tokens'));
        $this->assertEquals(1, $this->countApiCalls('status'));
    }

    /**
     * @test
     */
    public function itShouldNotReadEmailVerifiedOfPreviousPointOfContact()
    {
        $pointOfContactUuid = $this->faker->uuid;

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($pointOfContactUuid, 'tomorrow', ['email_verified' => false])),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid,
            $this->createStatusResponse($pointOfContactUuid)
        );

        $session->setToken((string) $this->makeUserToken($this->faker->uuid, '+1 hour', ['email_verified' => true]));

        $this->assertFalse($session->isEmailVerified());
    }

    /**
     * @test
     */
    public function itShouldNotCallApiForEmailVerifiedWithoutStoredToken()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createResponse(['message' => 'Error !'], 500),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $this->faker->uuid
        );

        $this->assertFalse($session->isEmailVerified());
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
     * @param string|null $pointOfContactUuid
     *
     * @return \PrestaShop\Module\PsAccounts\Http\Client\Response
     */
    private function createStatusResponse($pointOfContactUuid)
    {
        return $this->createResponse([
            'cloudShopId' => $this->faker->uuid,
            'isVerified' => true,
            'pointOfContactUuid' => $pointOfContactUuid,
            'pointOfContactEmail' => $pointOfContactUuid ? $this->faker->safeEmail : null,
        ], 200);
    }
}

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
            null,
            $this->createStatusResponse(null)
        );

        $storedToken = $this->makeUserToken($this->faker->uuid, 'yesterday', ['email_verified' => true]);
        $session->setToken((string) $storedToken, $this->faker->regexify('[a-zA-Z\d]{40}'));

        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals('', (string) $session->getValidToken(true));
        $this->assertFalse($session->isEmailVerified());
        // the empty cached status is confirmed once, then the stored token is cleaned up
        $this->assertEquals(['status'], $this->apiCalls);
        $this->assertEquals('', (string) $session->getToken());
        $this->assertEquals('', $this->configurationRepository->getUserFirebaseIdToken());
        $this->assertEquals('', $this->configurationRepository->getUserFirebaseRefreshToken());
    }

    /**
     * @test
     */
    public function itShouldServeStoredTokenWhenStatusNamesItsPointOfContactAgain()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $storedToken = $this->makeUserToken($pointOfContactUuid, '+1 hour');

        // the cached status names no point of contact, e.g. while it was transiently empty
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($pointOfContactUuid, 'tomorrow')),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            null,
            $this->createStatusResponse($pointOfContactUuid)
        );

        $session->setToken((string) $storedToken, $this->faker->regexify('[a-zA-Z\d]{40}'));

        $this->assertEquals((string) $storedToken, (string) $session->getValidToken());
        $this->assertEquals((string) $storedToken, (string) $session->getValidToken());
        $this->assertEquals((string) $storedToken, (string) $session->getToken());
        $this->assertEquals(['status'], $this->apiCalls);
    }

    /**
     * @test
     */
    public function itShouldKeepStoredTokenWithoutCachedPointOfContactWhenStatusCannotBeFetched()
    {
        $storedToken = $this->makeUserToken($this->faker->uuid, '+1 hour');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($this->faker->uuid, 'tomorrow')),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            null
        );

        $session->setToken((string) $storedToken, $this->faker->regexify('[a-zA-Z\d]{40}'));

        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertFalse($session->isEmailVerified());
        $this->assertEquals(['status'], $this->apiCalls);
        $this->assertEquals((string) $storedToken, $this->configurationRepository->getUserFirebaseIdToken());
    }

    /**
     * @test
     */
    public function itShouldNeitherServeNorRefreshAgainExpiredTokenWhenOwnerCannotBeTold()
    {
        $previousPointOfContactUuid = $this->faker->uuid;
        $storedToken = $this->makeUserToken($previousPointOfContactUuid, 'yesterday', ['email_verified' => true]);

        // accounts-api returns the new point of contact's token, the status can't be read
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($this->faker->uuid, 'tomorrow')),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $previousPointOfContactUuid
        );

        $session->setToken((string) $storedToken, $this->faker->regexify('[a-zA-Z\d]{40}'));

        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals(1, $this->countApiCalls('tokens'));
        $this->assertEquals(1, $this->countApiCalls('status'));
        // nor read
        $this->assertEquals('', (string) $session->getToken());
        $this->assertFalse($session->isEmailVerified());
        $this->assertEquals(1, $this->countApiCalls('tokens'));
        $this->assertEquals((string) $storedToken, $this->configurationRepository->getUserFirebaseIdToken());
    }

    /**
     * @test
     */
    public function itShouldServeTokenRewrittenIdenticalByRefresh()
    {
        $pointOfContactUuid = $this->faker->uuid;
        // an ID token issued twice within the same second is the same
        $refreshedToken = $this->makeUserToken($pointOfContactUuid, 'tomorrow');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($refreshedToken),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $session->setToken((string) $this->makeUserToken($pointOfContactUuid, 'yesterday'), $this->faker->regexify('[a-zA-Z\d]{40}'));

        $this->assertEquals((string) $refreshedToken, (string) $session->getValidToken(true));
        $this->assertEquals((string) $refreshedToken, (string) $session->getValidToken(true));
        $this->assertEquals((string) $refreshedToken, (string) $session->getToken());
        $this->assertEquals(2, $this->countApiCalls('tokens'));
    }

    /**
     * @test
     */
    public function itShouldCleanupTokenWithoutReadingStatusWhenShopIsNotIdentified()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($this->faker->uuid, 'tomorrow')),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            null,
            $this->createStatusResponse(null)
        );
        $this->mockedStatusManager->clearStatus();

        $session->setToken((string) $this->makeUserToken($this->faker->uuid, '+1 hour'), $this->faker->regexify('[a-zA-Z\d]{40}'));

        $this->assertEquals('', (string) $session->getValidToken());
        $this->assertEquals('', (string) $session->getValidToken(true));
        $this->assertFalse($session->isEmailVerified());
        $this->assertEquals([], $this->apiCalls);
        $this->assertEquals('', $this->configurationRepository->getUserFirebaseIdToken());
        $this->assertEquals('', $this->configurationRepository->getUserFirebaseRefreshToken());
    }

    /**
     * @test
     */
    public function itShouldServeRefreshedTokenWhateverTheLocalClock()
    {
        $pointOfContactUuid = $this->faker->uuid;
        // valid for accounts-api, already expired for a shop clock ahead of it
        $refreshedToken = $this->makeUserToken($pointOfContactUuid, '-1 hour');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($refreshedToken),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $session->setToken((string) $this->makeUserToken($pointOfContactUuid, '-2 hours'), $this->faker->regexify('[a-zA-Z\d]{40}'));

        $this->assertEquals((string) $refreshedToken, (string) $session->getValidToken());
        $this->assertEquals((string) $refreshedToken, $this->configurationRepository->getUserFirebaseIdToken());
        $this->assertEquals(1, $this->countApiCalls('tokens'));
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
    public function itShouldNeitherServeNorCleanupTokenWhenStatusCannotBeFetched()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $storedToken = $this->makeUserToken($this->faker->uuid, '+1 hour');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($pointOfContactUuid, 'tomorrow')),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid
        );

        $session->setToken((string) $storedToken);

        // whose token it is can't be told: kept, but not served
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals((string) $storedToken, $this->configurationRepository->getUserFirebaseIdToken());
        $this->assertEquals(1, $this->countApiCalls('status'));
        $this->assertEquals(0, $this->countApiCalls('tokens'));
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

        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertFalse($session->isEmailVerified());
        // the caller's setting is kept
        $this->assertTrue($this->mockedStatusManager->getThrowException(false));
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

        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', $this->configurationRepository->getUserFirebaseIdToken());
        // one token call per runtime; the status is read once per cache lifetime
        $this->assertEquals(1, $this->countApiCalls('tokens'));
        $this->assertEquals(1, $this->countApiCalls('status'));

        // next runtime: the status is read again, as the returned token disagrees with it
        Firebase\OwnerSession::resetNoTokenReturned();
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals(2, $this->countApiCalls('tokens'));
        $this->assertEquals(2, $this->countApiCalls('status'));
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

        $this->assertEquals('', (string) $session->getValidToken(false, false));
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
        // one token call per runtime until accounts-api is consistent (ACC-3736)
        $this->assertEquals(1, $this->countApiCalls('tokens'));
        $this->assertEquals(1, $this->countApiCalls('status'));

        // next runtime: the status is still fresh
        Firebase\OwnerSession::resetNoTokenReturned();
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals(2, $this->countApiCalls('tokens'));
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
        // one token call and one failed status call per runtime
        $this->assertEquals(1, $this->countApiCalls('tokens'));
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
        $this->assertEquals(1, $this->countApiCalls('tokens'));
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
                'cloudShopId' => $this->getMockedCloudShopId(),
                'pointOfContactUuid' => $pointOfContactUuid,
            ]),
        ]))->toArray()));

        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals(1, $this->countApiCalls('tokens'));
        $this->assertEquals(1, $this->countApiCalls('status'));
    }

    /**
     * @test
     */
    public function itShouldNotServeStoredTokenOfAnotherPointOfContactToReaders()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $this->faker->uuid
        );

        $session->setToken((string) $this->makeUserToken($this->faker->uuid, '+1 hour'));

        // e.g. UserTokenRepository::getToken(), the health check report
        $this->assertEquals('', (string) $session->getToken());
        $this->assertEquals([], $this->apiCalls);
    }

    /**
     * @test
     */
    public function itShouldReadEmailVerifiedOfNewPointOfContact()
    {
        $pointOfContactUuid = $this->faker->uuid;

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($this->makeUserToken($pointOfContactUuid, 'tomorrow', ['email_verified' => true])),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid,
            $this->createStatusResponse($pointOfContactUuid)
        );

        $session->setToken((string) $this->makeUserToken($this->faker->uuid, '+1 hour', ['email_verified' => false]));

        $this->assertTrue($session->isEmailVerified());
        $this->assertEquals(1, $this->countApiCalls('tokens'));
    }

    /**
     * @test
     */
    public function itShouldRetryUnreachableStatusOnceItsDelayIsOver()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $this->faker->uuid
        );

        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals(1, $this->countApiCalls('status'));

        // e.g. a long-running worker, 30 s later
        $property = new \ReflectionProperty(Firebase\FirebaseSession::class, 'statusUnavailableUntil');
        $property->setAccessible(true);
        $property->setValue(null, [$this->getMockedCloudShopId() => time() - 1]);
        Firebase\OwnerSession::resetNoTokenReturned();

        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals(2, $this->countApiCalls('status'));
    }

    /**
     * @test
     */
    public function itShouldNotShareUnreachableStatusBetweenShops()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $this->faker->uuid
        );
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals(1, $this->countApiCalls('status'));

        // another shop of a multistore, in the same runtime
        $this->mockedCloudShopId = null;
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $this->faker->uuid
        );
        $this->assertEquals('', (string) $session->getValidToken(false, false));
        $this->assertEquals(1, $this->countApiCalls('status'));
    }

    /**
     * @test
     */
    public function itShouldNotShareNoTokenReturnedBetweenShops()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $this->faker->uuid
        );
        // shop 1: a point of contact in the status, but no token returned (ACC-3736)
        $this->assertEquals('', (string) $session->getValidToken(false, false));

        // shop 2 of a multistore, same session in the same runtime (e.g. ShopProvider)
        $pointOfContactUuid = $this->faker->uuid;
        $token = $this->makeUserToken($pointOfContactUuid, '+1 hour');
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => date('Y-m-d H:i:s'),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->faker->uuid,
                'pointOfContactUuid' => $pointOfContactUuid,
            ]),
        ]))->toArray()));
        $session->setToken((string) $token);

        $this->assertEquals((string) $token, (string) $session->getValidToken(false, false));
        $this->assertEquals((string) $token, $this->configurationRepository->getUserFirebaseIdToken());
        $this->assertEquals(1, $this->countApiCalls('tokens'));
    }

    /**
     * @test
     */
    public function itShouldServeTokenStoredAfterNoTokenWasReturned()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $token = $this->makeUserToken($pointOfContactUuid, '+1 hour');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $pointOfContactUuid,
            $this->createStatusResponse($pointOfContactUuid)
        );

        $this->assertEquals('', (string) $session->getValidToken(false, false));

        // e.g. stored by a later Firebase shop token refresh, in the same runtime
        $session->setToken((string) $token);

        $this->assertEquals((string) $token, (string) $session->getValidToken(false, false));
        $this->assertEquals(1, $this->countApiCalls('tokens'));
    }

    /**
     * @test
     */
    public function itShouldReadStatusAgainWhenFreshCacheDisagreesWithReturnedToken()
    {
        $pointOfContactUuid = $this->faker->uuid;
        $newToken = $this->makeUserToken($pointOfContactUuid, 'tomorrow');

        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse($newToken),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            $this->faker->uuid,
            $this->createStatusResponse($pointOfContactUuid)
        );

        // previous point of contact cached a few seconds ago
        $status = json_decode($this->configurationRepository->getCachedShopStatus(), true);
        $status['updatedAt'] = date('Y-m-d H:i:s');
        $this->configurationRepository->updateCachedShopStatus(json_encode($status));

        $this->assertEquals((string) $newToken, (string) $session->getValidToken(false, false));
        $this->assertEquals(1, $this->countApiCalls('status'));
        $this->assertEquals(1, $this->countApiCalls('tokens'));
    }

    /**
     * @test
     */
    public function itShouldNotServeTokenWithoutSubject()
    {
        $session = $this->getMockedFirebaseSession(
            Firebase\OwnerSession::class,
            $this->createTokensResponse(null),
            $this->getMockedShopSession(new Token($this->makeJwtToken(new \DateTimeImmutable()))),
            null
        );

        $session->setToken((string) $this->makeJwtToken(new \DateTimeImmutable('+1 hour')));

        $this->assertEquals('', (string) $session->getToken());
    }

    /**
     * @test
     */
    public function itShouldForceRefreshForUnverifiedEmailOfNewPointOfContact()
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
        // the new point of contact's token, then a forced refresh as for any unverified email (main)
        $this->assertEquals(2, $this->countApiCalls('tokens'));
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
}

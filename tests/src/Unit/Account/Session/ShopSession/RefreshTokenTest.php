<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\ShopSession;

use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\AccessToken;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use \PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\SessionHelpers;

    /**
     * @inject
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * @inject
     *
     * @var OAuth2Client
     */
    protected $oauth2Client;

    /**
     * @inject
     *
     * @var OAuth2Service
     */
    protected $oAuth2Service;

    /**
     * @var \PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token
     */
    protected $validAccessToken;

    function set_up()
    {
        parent::set_up();

        $this->validAccessToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));
        $this->shopSession = $this->makeShopSession($this->validAccessToken);

        $this->shopSession->cleanup();
        $this->configurationRepository->updateClockOffset(0);

        // Shop is linked
        $this->statusManager->setCloudShopId($this->faker->uuid);

        // OAuth2Client exists
        $this->oauth2Client->update(
            $this->faker->uuid,
            $this->faker->password
        );
    }

    /**
     * @return void
     */
    public function tear_down()
    {
        parent::tear_down();

        $this->shopSession->cleanup();
        $this->configurationRepository->updateClockOffset(0);
    }

    /**
     * @param \PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token $accessToken
     * @param int $tokenExpirationLeeway
     *
     * @return ShopSession
     */
    private function makeShopSession($accessToken, $tokenExpirationLeeway = 0)
    {
        $oAuth2Service = $this->createMock(OAuth2Service::class);
        $oAuth2Service->method('getAccessTokenByClientCredentials')
            ->willReturn(new AccessToken([
                'access_token' => (string) $accessToken
            ]));
        $oAuth2Service->method('getOAuth2Client')
            ->willReturn($this->oauth2Client);

        return new ShopSession(
            $this->configurationRepository,
            $oAuth2Service,
            $this->module->getParameter('ps_accounts.accounts_api_url'),
            $tokenExpirationLeeway
        );
    }

    /**
     * Local clock 5 minutes behind the authorization server: the token expires 10 s
     * after it has been issued, so it is already within the leeway on the server side.
     *
     * @test
     */
    public function itShouldCorrectExpirationWithClockOffsetMeasuredOnRefresh()
    {
        $accessToken = $this->makeJwtToken(
            new \DateTimeImmutable('+310 seconds'),
            [],
            new \DateTimeImmutable('+300 seconds')
        );

        $token = $this->makeShopSession($accessToken, 60)->refreshToken();

        $this->assertGreaterThanOrEqual(299, $this->configurationRepository->getClockOffset());
        $this->assertLessThanOrEqual(300, $this->configurationRepository->getClockOffset());
        $this->assertTrue($token->isExpired());
        $this->assertFalse((new Token((string) $accessToken, null, 60))->isExpired());
    }

    /**
     * @test
     */
    public function itShouldResetClockOffsetBelowThreshold()
    {
        $this->configurationRepository->updateClockOffset(120);

        $this->shopSession->refreshToken();

        $this->assertSame(0, $this->configurationRepository->getClockOffset());
    }

    /**
     * @test
     */
    public function itShouldIgnoreBogusClockOffset()
    {
        $accessToken = $this->makeJwtToken(
            new \DateTimeImmutable('+3 days'),
            [],
            new \DateTimeImmutable('+2 days')
        );

        $this->makeShopSession($accessToken)->refreshToken();

        $this->assertSame(0, $this->configurationRepository->getClockOffset());
    }

    /**
     * Local clock 2 hours ahead: not corrected, a negative offset could go stale.
     *
     * @test
     */
    public function itShouldNotCorrectLocalClockAhead()
    {
        $this->configurationRepository->updateClockOffset(120);
        $accessToken = $this->makeJwtToken(
            new \DateTimeImmutable('-1 hour'),
            [],
            new \DateTimeImmutable('-2 hours')
        );

        $this->makeShopSession($accessToken)->refreshToken();

        $this->assertSame(0, $this->configurationRepository->getClockOffset());
    }

    /**
     * @test
     */
    public function itShouldNotRewriteClockOffsetWithinWriteThreshold()
    {
        $this->configurationRepository->updateClockOffset(120);
        $this->makeShopSession($this->makeJwtToken(
            new \DateTimeImmutable('+2 hours'),
            [],
            new \DateTimeImmutable('+115 seconds')
        ))->refreshToken();

        $this->assertSame(120, $this->configurationRepository->getClockOffset());

        $this->configurationRepository->updateClockOffset(0);
        $this->makeShopSession($this->makeJwtToken(
            new \DateTimeImmutable('+2 hours'),
            [],
            new \DateTimeImmutable('+7 seconds')
        ))->refreshToken();

        $this->assertSame(0, $this->configurationRepository->getClockOffset());
    }

    /**
     * @test
     */
    public function itShouldNotFailRefreshWhenClockOffsetCannotBeStored()
    {
        $accessToken = $this->makeJwtToken(
            new \DateTimeImmutable('+2 hours'),
            [],
            new \DateTimeImmutable('+300 seconds')
        );

        $configurationRepository = $this->createMock(ConfigurationRepository::class);
        $configurationRepository->method('getClockOffset')->willReturn(0);
        $configurationRepository->method('updateClockOffset')
            ->willThrowException(new \Exception('Unable to write configuration'));
        $configurationRepository->expects($this->once())
            ->method('updateAccessToken')
            ->with((string) $accessToken);
        $configurationRepository->method('getAccessToken')->willReturn((string) $accessToken);

        $oAuth2Service = $this->createMock(OAuth2Service::class);
        $oAuth2Service->method('getAccessTokenByClientCredentials')
            ->willReturn(new AccessToken([
                'access_token' => (string) $accessToken,
            ]));

        $shopSession = new ShopSession(
            $configurationRepository,
            $oAuth2Service,
            $this->module->getParameter('ps_accounts.accounts_api_url')
        );

        $this->assertInstanceOf(Token::class, $shopSession->refreshToken());
    }

    /**
     * @test
     */
    public function itShouldRefreshToken()
    {
        $e = null;
        try {
            // Shop is linked
            $this->statusManager->setCloudShopId($this->faker->uuid);

            // OAuth2Client exists
            $this->oauth2Client->update(
                $this->faker->uuid,
                $this->faker->password
            );

            $token = $this->shopSession->refreshToken();
        } catch (RefreshTokenException $e) {
            //$this->module->getLogger()->info($e->getMessage());
        }

        $this->assertNull($e);
        $this->assertEquals((string) $this->validAccessToken, (string) $token->getJwt());
        $this->assertEquals("", (string) $token->getRefreshToken());
        $this->assertEquals(new Token($this->validAccessToken), $token);
    }
}

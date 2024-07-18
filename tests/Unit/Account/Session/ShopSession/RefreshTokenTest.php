<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\ShopSession;

use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Token\AccessToken;

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
     * @var Oauth2Client
     */
    protected $oauth2Client;

    public function tearDown()
    {
        parent::tearDown();

        $this->shopSession->cleanup();
    }

    /**
     * @test
     */
    public function itShouldClearConfigurationAndThrowIfNoOauth()
    {
        $shopSession = $this->createMockedSession(null, false, true);
        $shopSession->cleanup();

        $accessToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
        ]);
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));
        $shopSession->setToken((string) $accessToken, (string) $refreshToken);

        $e = null;
        try {
            $shopSession->refreshToken();
        } catch (\Exception $e) {}

        $this->assertInstanceOf(RefreshTokenException::class, $e);
        $token = $shopSession->getToken();
        $this->assertEquals('', (string) $token->getJwt());
        $this->assertEquals('', $token->getRefreshToken());
    }

    /**
     * @test
     */
    public function itShouldNotClearConfigurationIfShopIsUnlinked()
    {
        $newAccessToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));
        $shopSession = $this->createMockedSession(new AccessToken([
            'access_token' => (string)$newAccessToken
        ]), false, false);
        $shopSession->cleanup();

        $this->oauth2Client->update($this->faker->uuid, $this->faker->uuid);

        $token = $shopSession->refreshToken();

        $this->assertEquals((string) $newAccessToken, (string) $shopSession->getToken()->getJwt());
        $this->assertEquals(new Token($newAccessToken), $token);
    }

    /**
     * @test
     */
    public function itShouldRefreshToken()
    {
        $newAccessToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));
        $shopSession = $this->createMockedSession(new AccessToken([
            'access_token' => (string)$newAccessToken
        ]), true, true);
        $shopSession->cleanup();

        $this->oauth2Client->update($this->faker->uuid, $this->faker->uuid);

        $token = $shopSession->refreshToken();

        $this->assertEquals((string) $newAccessToken, (string) $shopSession->getToken()->getJwt());
        $this->assertEquals(new Token($newAccessToken), $token);
    }

    /**
     * @param AccessToken|null $tokenResponse
     * @param bool $oauth2ClientExists
     * @param bool $linkShopExists
     *
     * @return ShopSession
     */
    private function createMockedSession($tokenResponse, $oauth2ClientExists = true, $linkShopExists = true)
    {
        $shopProvider = $this->getMockBuilder(ShopProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $oauth2Client = $this->getMockBuilder(Oauth2Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $linkShop = $this->getMockBuilder(LinkShop::class)
            ->disableOriginalConstructor()
            ->getMock();

        $shopProvider->method('getAccessToken')->willReturn($tokenResponse);
        $shopProvider->method('getOauth2Client')->willReturn($oauth2Client);
        $oauth2Client->method('exists')->willReturn($oauth2ClientExists);
        $linkShop->method('exists')->willReturn($linkShopExists);

        return new ShopSession(
            $this->configurationRepository,
            $shopProvider,
            $linkShop,
            $this->commandBus
        );
    }
}

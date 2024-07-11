<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\ShopSession;

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

    function tearDown(): void
    {
        parent::tearDown();
        $this->shopSession->cleanup();
    }

    /**
     * @test
     */
    public function itShouldClearConfigurationAndThrowIfNotOauth()
    {
        $shopSession = $this->createMockedSession(false, null);
        $shopSession->cleanup();

        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
        ]);
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));
        $shopSession->setToken((string) $idToken, (string) $refreshToken);
        try {
            $shopSession->refreshToken();
        } catch (\Exception $e) {
            $this->assertInstanceOf(RefreshTokenException::class, $e);
            $token = $shopSession->getOrRefreshToken();
            $this->assertEquals("", $token->getJwt());
            $this->assertEquals("", $token->getRefreshToken());
            return;
        }
        $this->fail('Test should have throw');
    }

    /**
     * @test
     */
    public function itShouldRefreshToken()
    {
        $newAccessToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));
        $shopSession = $this->createMockedSession(true, new AccessToken([
            'access_token' => $newAccessToken->toString()
        ]));
        $shopSession->cleanup();

        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;

        $this->configurationRepository->updateOauth2ClientId($clientId);
        $this->configurationRepository->updateOauth2ClientSecret($clientSecret);

        try {
            $token = $shopSession->refreshToken();
        } catch (\Exception $e) {
            $this->fail('Test shouldn\'t throw');
        }

        $this->assertEquals($this->configurationRepository->getAccessToken(), $newAccessToken->toString());
        $this->assertEquals($token, new Token($newAccessToken));
    }

    /**
     * @param $existResponse boolean
     * @param $tokenResponse AccessToken
     * @return ShopSession
     */
    private function createMockedSession($existResponse, $tokenResponse) {
        $shopProvider = $this->getMockBuilder(ShopProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Client = $this->getMockBuilder(Oauth2Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Client->method('exists')->willReturn($existResponse);
        if ($tokenResponse) {
            $shopProvider->method('getAccessToken')->willReturn($tokenResponse);
        } else {
            $shopProvider->method('getAccessToken')->willThrowException(new \Exception('Fail !!'));
        }
        $shopProvider->method('getOauth2Client')->willReturn($oauth2Client);
        return new ShopSession(
            $this->configurationRepository,
            $shopProvider,
            $this->linkShop,
            $this->commandBus
        );
    }
}

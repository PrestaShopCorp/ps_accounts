<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\Oauth2\ShopSession;

use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
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
     * @test
     * @throws \ReflectionException
     */
    public function itShouldClearConfigurationAndThrowIfNotOauth()
    {
        $this->shopSession->cleanup();
        $this->createMockedSession(false, null);

        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
        ]);
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));
        $this->shopSession->setToken((string) $idToken, (string) $refreshToken);
        try {
            $this->shopSession->refreshToken();
        } catch (\Exception $e) {
            $this->assertInstanceOf(RefreshTokenException::class, $e);
            $token = $this->shopSession->getOrRefreshToken();
            $this->assertEquals("", $token->getJwt());
            $this->assertEquals("", $token->getRefreshToken());
            return;
        }
        $this->fail('Test should have throw');
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function itShouldRefreshToken()
    {
        $this->shopSession->cleanup();
        $newAccessToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));
        $this->createMockedSession(true, new AccessToken([
            'access_token' => $newAccessToken->toString()
        ]));

        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;

        $this->configurationRepository->updateOauth2ClientId($clientId);
        $this->configurationRepository->updateOauth2ClientSecret($clientSecret);

        try {
            $token = $this->shopSession->refreshToken();
        } catch (\Exception $e) {
            $this->fail('Test shouldn\'t throw');
        }

        $this->assertEquals($this->configurationRepository->getAccessToken(), $newAccessToken->toString());
        $this->assertEquals($token, new Token($newAccessToken));
    }

    /**
     * @throws \ReflectionException
     */
    private function createMockedSession($existResponse, $tokenResponse) {
        $shopProvider = $this->getMockBuilder(\PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Client = $this->getMockBuilder(\PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Client->method('exists')->willReturn($existResponse);
        $shopProvider->method('getAccessToken')->willReturn($tokenResponse);
        $shopProvider->method('getOauth2Client')->willReturn($oauth2Client);
        $this->replaceDependency($this->shopSession, 'oauth2ClientProvider', $shopProvider);
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\OAuth2;

use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;

class TestCase extends \PrestaShop\Module\PsAccounts\Tests\TestCase
{
    /**
     * @var OAuth2Service
     */
    protected $oAuth2Service;

    /**
     * @var Response
     */
    protected $wellKnownResponse;

    /**
     * @var Response
     */
    protected $accessTokenResponse;

    /**
     * @var Response
     */
    protected $resourceOwnerResponse;

    /**
     * @var Response
     */
    protected $jwksResponse;

    /**
     * @return Client|(Client&\PHPUnit_Framework_MockObject_MockObject)|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockedHttpClient()
    {
        $client = $this->createMock(Client::class);

        $client->method('get')
            ->willReturnCallback(function ($route) {
                if (preg_match('/jwks\.json$/', $route)) {
                    return $this->jwksResponse;
                }
                if (preg_match('/openid\-configuration/', $route)) {
                    return $this->wellKnownResponse;
                }
                if (preg_match('/userinfo/', $route)) {
                    return $this->resourceOwnerResponse;
                }
            });

        $client->method('post')
            ->willReturnCallback(function ($route) {
                if (preg_match('/oauth2\/token/', $route)) {
                    return $this->accessTokenResponse;
                }
            });

        return $client;
    }

    /**
     * @return string
     */
    protected function getTestBaseDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '../../../..';
    }

    /**
     * @return string
     */
    protected function getTestCacheDir()
    {
        return $this->getTestBaseDir() . '/var/cache/';
    }
}

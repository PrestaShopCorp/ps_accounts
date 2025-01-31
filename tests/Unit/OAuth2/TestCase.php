<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\OAuth2;

use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\OAuth2\ApiClient;

class TestCase extends \PrestaShop\Module\PsAccounts\Tests\TestCase
{
    /**
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Response|(Response&\PHPUnit_Framework_MockObject_MockObject)
     */
    protected $wellKnownResponse;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Response|(Response&\PHPUnit_Framework_MockObject_MockObject)
     */
    protected $accessTokenResponse;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Response|(Response&\PHPUnit_Framework_MockObject_MockObject)
     */
    protected $resourceOwnerResponse;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|(\PHPUnit_Framework_MockObject_MockObject&Response)|Response
     */
    protected $jwksResponse;

    /**
     * @param mixed $responseBody
     * @param int $statusCode
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Response|(Response&\PHPUnit_Framework_MockObject_MockObject)
     */
    protected function createResponse($responseBody, $statusCode = 200)
    {
        return new Response(
            \json_decode($responseBody, true),
            $statusCode
        );
    }

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
        return __DIR__ . DIRECTORY_SEPARATOR . '../..';
    }

    /**
     * @return string
     */
    protected function getTestCacheDir()
    {
        return $this->getTestBaseDir() . '/var/cache/';
    }
}

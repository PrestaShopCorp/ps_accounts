<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\FirebaseSession;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;

trait SessionHelpers
{
    /**
     * @param Token $token
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ShopSession
     */
    protected function getMockedShopSession(Token $token)
    {
        $shopSession = $this->getMockBuilder(ShopSession::class)
            ->setConstructorArgs([
                $this->configurationRepository,
                $this->shopProvider,
            ])
            ->enableOriginalClone()
            ->setMethods(['getOrRefreshToken'])
            ->getMock();
        $shopSession->method('getOrRefreshToken')->willReturn($token);
        return $shopSession;
    }

    /**
     * @param string $firebaseSessionClass
     * @param array $response
     * @param ShopSession $shopSession
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FirebaseSession
     */
    protected function getMockedFirebaseSession($firebaseSessionClass, array $response, $shopSession)
    {
        $client = $this->createMock(AccountsClient::class);
        $client->method('firebaseTokens')->willReturn($response);

        $firebaseSession = $this->getMockBuilder($firebaseSessionClass)
            ->setConstructorArgs([
                $this->configurationRepository,
                $shopSession,
            ])
            ->enableOriginalClone()
            ->setMethods(['getAccountsClient'])
            ->getMock();
        $firebaseSession->method('getAccountsClient')->willReturn($client);
        return $firebaseSession;
    }
}

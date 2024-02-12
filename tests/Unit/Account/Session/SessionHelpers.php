<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session;

use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;

trait SessionHelpers
{
    /**
     * @param array $response firebase tokens response
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ShopSession
     */
    protected function getMockedShopSession(array $response)
    {
        $client = $this->createMock(AccountsClient::class);
        $client->method('firebaseTokens')->willReturn($response);

        $shopSession = $this->getMockBuilder(ShopSession::class)
            ->setConstructorArgs([
                $this->configurationRepository,
                $this->shopProvider,
            ])
            ->enableOriginalClone()
            ->setMethods(['getAccountsClient'])
            ->getMock();
        $shopSession->method('getAccountsClient')->willReturn($client);
        return $shopSession;
    }
}

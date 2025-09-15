<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\CachedShopStatus;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\FirebaseSession;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;

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
                $this->oAuth2Service,
                $this->statusManager,
                $this->commandBus
            ])
            ->enableOriginalClone()
            ->setMethods(['getValidToken'])
            ->getMock();
        $shopSession->method('getValidToken')->willReturn($token);
        return $shopSession;
    }

    /**
     * @param string $firebaseSessionClass
     * @param Response $response
     * @param ShopSession $shopSession
     *
     * @return FirebaseSession&MockObject
     */
    protected function getMockedFirebaseSession($firebaseSessionClass, Response $response, $shopSession)
    {
        $client = $this->createMock(Client::class);

        $cloudShopId = $this->faker->uuid;

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
            ])
        ]))->toArray()));

        /** @var AccountsService $accountsService */
        $accountsService = $this->module->getService(AccountsService::class);
        $accountsService->setClient($client);

        $client->method('get')
            ->willReturnCallback(function ($route) use ($response) {
                if (preg_match('/v1\/shop-identities\/(.*)\/tokens$/', $route)) {
                    return $response;
                }
                return $this->createResponse([], 500, true);
            });

        /** @var FirebaseSession&MockObject $firebaseSession */
        $firebaseSession = $this->getMockBuilder($firebaseSessionClass)
            ->setConstructorArgs([
                $this->configurationRepository,
                $shopSession,
            ])
            ->enableOriginalClone()
            ->setMethods(['getAccountsService'])
            ->getMock();
        $firebaseSession->method('getAccountsService')
            ->willReturn($accountsService);

        return $firebaseSession;
    }
}

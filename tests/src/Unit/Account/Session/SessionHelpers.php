<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\CachedShopStatus;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\FirebaseSession;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
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
                '',
                0,
            ])
            ->enableOriginalClone()
            ->setMethods(['getValidToken'])
            ->getMock();
        $shopSession->method('getValidToken')->willReturn($token);

        return $shopSession;
    }

    /**
     * @var string[] routes called on accounts-api
     */
    protected $apiCalls = [];

    /**
     * @var StatusManager status manager of the last mocked Firebase session
     */
    protected $mockedStatusManager;

    /**
     * @param string $firebaseSessionClass
     * @param Response $response /tokens response
     * @param ShopSession $shopSession
     * @param string|null $pointOfContactUuid point of contact of the cached status
     * @param Response|null $statusResponse /status response (error by default)
     *
     * @return FirebaseSession&MockObject
     */
    protected function getMockedFirebaseSession(
        $firebaseSessionClass,
        Response $response,
        $shopSession,
        $pointOfContactUuid = null,
        Response $statusResponse = null
    ) {
        $client = $this->createMock(Client::class);

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            // expired: a status call is allowed when needed
            'updatedAt' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->faker->uuid,
                'pointOfContactUuid' => $pointOfContactUuid,
            ]),
        ]))->toArray()));

        /** @var AccountsService $accountsService */
        $accountsService = $this->module->getService(AccountsService::class);
        $accountsService->setClient($client);

        $this->apiCalls = [];
        $client->method('get')
            ->willReturnCallback(function ($route) use ($response, $statusResponse) {
                if (preg_match('/v1\/shop-identities\/(.*)\/tokens$/', $route)) {
                    $this->apiCalls[] = 'tokens';

                    return $response;
                }
                if (preg_match('/v1\/shop-identities\/(.*)\/status$/', $route)) {
                    $this->apiCalls[] = 'status';

                    return $statusResponse ?: $this->createResponse([], 500, true);
                }

                return $this->createResponse([], 500, true);
            });

        $statusManager = new StatusManager($shopSession, $accountsService, $this->configurationRepository);
        $this->mockedStatusManager = $statusManager;

        /** @var FirebaseSession&MockObject $firebaseSession */
        $firebaseSession = $this->getMockBuilder($firebaseSessionClass)
            ->setConstructorArgs([
                $this->configurationRepository,
                $shopSession,
            ])
            ->enableOriginalClone()
            ->setMethods(['getAccountsService', 'getStatusManager'])
            ->getMock();
        $firebaseSession->method('getAccountsService')
            ->willReturn($accountsService);
        $firebaseSession->method('getStatusManager')
            ->willReturn($statusManager);

        return $firebaseSession;
    }

    /**
     * @param string $route
     *
     * @return int
     */
    protected function countApiCalls($route)
    {
        return count(array_filter($this->apiCalls, function ($call) use ($route) {
            return $call === $route;
        }));
    }
}

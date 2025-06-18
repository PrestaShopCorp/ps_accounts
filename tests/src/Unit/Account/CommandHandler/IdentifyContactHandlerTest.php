<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\CachedShopStatus;
use PrestaShop\Module\PsAccounts\Account\Command\IdentifyContactCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\IdentifyContactHandler;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\IdentityCreated;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\AccessToken;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IdentifyContactHandlerTest extends TestCase
{
    /**
     * @var AccountsService&MockObject
     */
    protected $accountsService;

    /**
     * @inject
     *
     * @var StatusManager
     */
    public $statusManager;

    /**
     * @var ShopSession&MockObject
     */
    protected $shopSession;

    /**
     * @var Client&MockObject
     */
    public $client;

    /**
     * @var int
     */
    protected $shopId = 1;

    public function set_up()
    {
        parent::set_up();

        $this->client = $this->createMock(Client::class);
        $this->shopSession = $this->createMock(ShopSession::class);
        $this->accountsService = $this->createMock(AccountsService::class);
        $this->accountsService->setClient($this->client);
    }

    /**
     * @test
     */
    public function itShouldSaveIdentityContact()
    {
        $cloudShopId = $this->faker->uuid;

        $this->statusManager->setCloudShopId($cloudShopId);

        $this->shopSession->method('getValidToken')->willReturn("valid_token");

        // Expected call to setPointOfContact with correct parameters
        $this->accountsService->expects($this->once())
            ->method('setPointOfContact')
            ->with(
                $this->equalTo($cloudShopId),
                $this->equalTo("valid_token"),
                $this->equalTo("valid_access_token")
            );

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
                'isVerified' => true,
            ])
        ]))->toArray()));

        $this->getHandler()->handle(new IdentifyContactCommand(new AccessToken([
            'access_token' => 'valid_access_token',
        ])));

        $this->assertTrue($this->statusManager->cacheInvalidated());
    }

    /**
     * @test
     */
    public function itShouldNotSaveIdentityContactOnShopNotVerified()
    {
        $cloudShopId = $this->faker->uuid;

        $this->statusManager->setCloudShopId($cloudShopId);

        $this->shopSession->method('getValidToken')->willReturn("valid_token");

        // Expected call to setPointOfContact with correct parameters
        $this->accountsService->expects($this->exactly(0))
            ->method('setPointOfContact')
            ->with(
                $this->equalTo($cloudShopId),
                $this->equalTo("valid_token"),
                $this->equalTo("valid_access_token")
            );

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
                'isVerified' => false,
            ])
        ]))->toArray()));

        $this->getHandler()->handle(new IdentifyContactCommand(new AccessToken(
            [
                'access_token' => 'valid_access_token',
            ]
        )));
    }

    /**
     * @return IdentifyContactHandler
     */
    private function getHandler()
    {
        return new IdentifyContactHandler(
            $this->accountsService,
            $this->statusManager,
            $this->shopSession
        );
    }
}

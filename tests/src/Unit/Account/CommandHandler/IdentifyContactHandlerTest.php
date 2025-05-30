<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\Command\IdentifyContactCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\IdentifyContactHandler;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\AccessToken;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IdentifyContactHandlerTest extends TestCase
{
    /**
     * @inject
     *
     * @var AccountsService
     */
    protected $accountsService;

    /**
     * @inject
     *
     * @var StatusManager
     */
    public $statusManager;

    /**
     * @inject
     *
     * @var ShopSession
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
        $pointOfContactEmail = $this->faker->email;
        $pointOfContactUuid = $this->faker->uuid;
        $cloudShopId = $this->faker->uuid;

        $this->statusManager->setCloudShopId($cloudShopId);

        $this->shopSession->method('getValidToken')->willReturn("valid_token");

        $this->client->method('post')
            ->willReturnCallback(function ($route) use ($pointOfContactEmail, $pointOfContactUuid, $cloudShopId) {
                if (preg_match('/v1\/shop-identities\/' . $cloudShopId . '\/point-of-contact$/', $route)) {
                    return $this->createResponse([
                        'pointOfContactEmail' => $pointOfContactEmail,
                        'pointOfContactUuid' => $pointOfContactUuid,
                    ], 200, true);
                }
                return $this->createResponse([], 500, true);
            });

        // Expected call to setPointOfContact with correct parameters
        $this->accountsService->expects($this->once())
            ->method('setPointOfContact')
            ->with(
                $this->equalTo($cloudShopId),
                $this->equalTo("valid_token"),
                $this->equalTo(null)
            );

        $this->statusManager->setCachedStatus(new ShopStatus([
            'cloudShopId' => $cloudShopId,
            'isVerified' => true,
        ]));

        $this->getHandler()->handle(new IdentifyContactCommand(new AccessToken()));
    }

    /**
     * @test
     */
    public function itShouldNotSaveIdentityContactOnShopNotVerified()
    {
        $pointOfContactEmail = $this->faker->email;
        $pointOfContactUuid = $this->faker->uuid;
        $cloudShopId = $this->faker->uuid;

        $this->statusManager->setCloudShopId($cloudShopId);

        $this->shopSession->method('getValidToken')->willReturn("valid_token");

        $this->client->method('post')
            ->willReturnCallback(function ($route) use ($pointOfContactEmail, $pointOfContactUuid, $cloudShopId) {
                if (preg_match('/v1\/shop-identities\/' . $cloudShopId . '\/point-of-contact$/', $route)) {
                    return $this->createResponse([
                        'pointOfContactEmail' => $pointOfContactEmail,
                        'pointOfContactUuid' => $pointOfContactUuid,
                    ], 200, true);
                }
                return $this->createResponse([], 500, true);
            });

        // Expected call to setPointOfContact with correct parameters
        $this->accountsService->expects($this->exactly(0))
            ->method('setPointOfContact')
            ->with(
                $this->equalTo($cloudShopId),
                $this->equalTo("valid_token"),
                $this->equalTo(null)
            );

        $this->statusManager->setCachedStatus(new ShopStatus([
            'cloudShopId' => $cloudShopId,
            'isVerified' => false,
        ]));

        $this->getHandler()->handle(new IdentifyContactCommand(new AccessToken()));
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

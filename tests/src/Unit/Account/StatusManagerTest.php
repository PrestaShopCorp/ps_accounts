<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class StatusManagerTest extends TestCase
{
    /**
     * @inject
     *
     * @var AccountsService
     */
    protected $accountsService;

    /**
     * @var ShopSession&MockObject
     */
    protected $shopSession;

    /**
     * @var StatusManager
     */
    public $statusManager;

    /**
     * @var Client&MockObject
     */
    public $client;

    public function set_up()
    {
        parent::set_up();

        $this->client = $this->createMock(Client::class);
        $this->accountsService->setClient($this->client);
        $this->shopSession = $this->createMock(ShopSession::class);

        $this->statusManager = new StatusManager(
            $this->shopSession,
            $this->accountsService,
            $this->configurationRepository
        );
    }

    /**
     * @test
     */
    public function itShouldSetCachedStatus()
    {
        $cloudShopId = $this->faker->uuid;

        $this->statusManager->setCachedStatus(new ShopStatus([
            'cloudShopId' => $cloudShopId,
            'isVerified' => false,
        ]));

        $cachedStatus = $this->statusManager->getCachedStatus();

        $this->assertEquals($cloudShopId, $cachedStatus->cloudShopId);
        $this->assertFalse($cachedStatus->isVerified);
    }

    /**
     * @test
     */
    public function itShouldSetOrUpdateCachedStatus()
    {
        $cloudShopId = $this->faker->uuid;
        $pointOfContactEmail = $this->faker->email;

        $this->statusManager->setCachedStatus(new ShopStatus([
            'cloudShopId' => $cloudShopId,
            'isVerified' => false,
        ]));

        $this->statusManager->upsetCachedStatus(new ShopStatus([
            'pointOfContactEmail' => $pointOfContactEmail,
            'isVerified' => true,
        ]));

        $cachedStatus = $this->statusManager->getCachedStatus();

        $this->assertEquals($cloudShopId, $cachedStatus->cloudShopId);
        $this->assertTrue($cachedStatus->isVerified);
        $this->assertEquals($pointOfContactEmail, $cachedStatus->pointOfContactEmail);
    }

    /**
     * @test
     */
    public function itShouldThrowOnUnsetCachedStatus()
    {
        $this->configurationRepository->updateShopStatus(null);

        $this->expectException(UnknownStatusException::class);

        $this->statusManager->getCachedStatus();
    }

    /**
     * @test
     */
    public function itShouldUpdateStatusFromCloud()
    {
        $cloudShopId = $this->faker->uuid;
        $pointOfContactEmail = $this->faker->email;
        $pointOfContactUid = $this->faker->uuid;

        $this->configurationRepository->updateShopStatus(null);

        $this->shopSession->method('getValidToken')
            ->willReturn($this->faker->uuid);

        $this->client->method('get')
            ->willReturnCallback(function ($route) use ($cloudShopId, $pointOfContactEmail, $pointOfContactUid) {
                if (preg_match('/v1\/shop-status$/', $route)) {
                    return $this->createResponse([
                        "cloudShopId" => $cloudShopId,
                        "isVerified" => true,
                        "pointOfContactEmail" => $pointOfContactEmail,
                        "pointOfContactUid" => $pointOfContactUid,
                    ], 200, true);
                }
                return $this->createResponse([], 500, true);
            });

        sleep(1);

        $cachedStatus = $this->statusManager->getStatus(true, 1);

        $this->assertEquals($cloudShopId, $cachedStatus->cloudShopId);
        $this->assertTrue($cachedStatus->isVerified);
        $this->assertEquals($pointOfContactEmail, $cachedStatus->pointOfContactEmail);
    }

    /**
     * @test
     */
    public function itShouldNotUpdateStatusFromCloudOnError()
    {
        $this->statusManager->setCachedStatus(new ShopStatus([]));

        $this->shopSession->method('getValidToken')
            ->willReturn($this->faker->uuid);

        $this->client->method('get')
            ->willReturnCallback(function ($route) {
                if (preg_match('/v1\/shop-status$/', $route)) {
                    return $this->createResponse([
                        "msg" => "Invalid request",
                    ], 400, true);
                }
                return $this->createResponse([], 500, true);
            });

        sleep(1);

        $cachedStatus = $this->statusManager->getStatus(true, 1);

        var_export($cachedStatus->toArray());

        $this->assertNull($cachedStatus->cloudShopId);
        $this->assertFalse($cachedStatus->isVerified);
        $this->assertNull($cachedStatus->pointOfContactEmail);
    }
}

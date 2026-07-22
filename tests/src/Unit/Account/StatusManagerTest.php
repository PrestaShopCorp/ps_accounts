<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\CachedShopStatus;
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

//    /**
//     * @test
//     */
//    public function itShouldSetCachedStatus()
//    {
//        $cloudShopId = $this->faker->uuid;
//
//        $this->statusManager->setCachedStatus(new CachedShopStatus([
//            'shopStatus' => new ShopStatus([
//                'cloudShopId' => $cloudShopId,
//                'isVerified' => false,
//            ]),
//        ]));
//
//        $cachedStatus = $this->statusManager->getStatus(StatusManager::CACHE_TTL_INFINITE);
//
//        $this->assertEquals($cloudShopId, $cachedStatus->cloudShopId);
//        $this->assertFalse($cachedStatus->isVerified);
//    }
//
//    /**
//     * @test
//     */
//    public function itShouldSetOrUpdateCachedStatus()
//    {
//        $cloudShopId = $this->faker->uuid;
//        $pointOfContactEmail = $this->faker->email;
//
//        $this->statusManager->setCachedStatus(new ShopStatus([
//            'cloudShopId' => $cloudShopId,
//            'isVerified' => false,
//        ]));
//
//        $this->statusManager->upsetCachedStatus(new ShopStatus([
//            'pointOfContactEmail' => $pointOfContactEmail,
//            'isVerified' => true,
//        ]));
//
//        $cachedStatus = $this->statusManager->getCachedStatus();
//
//        $this->assertEquals($cloudShopId, $cachedStatus->cloudShopId);
//        $this->assertTrue($cachedStatus->isVerified);
//        $this->assertEquals($pointOfContactEmail, $cachedStatus->pointOfContactEmail);
//    }

    /**
     * @test
     */
    public function itShouldThrowOnUnsetCachedStatus()
    {
        $this->configurationRepository->updateCachedShopStatus(null);

        $this->expectException(UnknownStatusException::class);

        $this->statusManager->getStatus(true);
    }

    /**
     * @test
     */
    public function itShouldUpdateStatusFromCloudWhenTtlExpired()
    {
        $cloudShopId = $this->faker->uuid;
        $pointOfContactEmail = $this->faker->email;
        $pointOfContactUid = $this->faker->uuid;

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
                'isVerified' => false,
            ])
        ]))->toArray()));

        $this->shopSession->method('getValidToken')
            ->willReturn($this->faker->uuid);

        $this->client->method('get')
            ->with($this->matchesRegularExpression('/v1\/shop-identities\/' . $cloudShopId . '\/status$/'))
            ->willReturn($this->createResponse([
                "cloudShopId" => $cloudShopId,
                "isVerified" => true,
                "pointOfContactEmail" => $pointOfContactEmail,
                "pointOfContactUid" => $pointOfContactUid,
            ], 200));

        sleep(1);

        $cachedStatus = $this->statusManager->getStatus(false, 1);

        $this->assertEquals($cloudShopId, $cachedStatus->cloudShopId);
        $this->assertTrue($cachedStatus->isVerified);
        $this->assertEquals($pointOfContactEmail, $cachedStatus->pointOfContactEmail);
    }

    /**
     * @test
     */
    public function itShouldUpdateStatusFromCloudWhenCacheInvalidated()
    {
        $cloudShopId = $this->faker->uuid;
        $pointOfContactEmail = $this->faker->email;
        $pointOfContactUid = $this->faker->uuid;

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
                'isVerified' => false,
            ])
        ]))->toArray()));

        $this->shopSession->method('getValidToken')
            ->willReturn($this->faker->uuid);

        $this->client->method('get')
            ->with($this->matchesRegularExpression('/v1\/shop-identities\/' . $cloudShopId . '\/status$/'))
            ->willReturn($this->createResponse([
                "cloudShopId" => $cloudShopId,
                "isVerified" => true,
                "pointOfContactEmail" => $pointOfContactEmail,
                "pointOfContactUid" => $pointOfContactUid,
            ], 200));

        $this->statusManager->invalidateCache();
        $cachedStatus = $this->statusManager->getStatus();

        $this->assertEquals($cloudShopId, $cachedStatus->cloudShopId);
        $this->assertTrue($cachedStatus->isVerified);
        $this->assertEquals($pointOfContactEmail, $cachedStatus->pointOfContactEmail);
    }

    /**
     * @test
     */
    public function itShouldNotUpdateStatusFromCloudIfTtlNotExpired()
    {
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->faker->uuid,
            ]),
        ]))->toArray()));

        $this->shopSession->method('getValidToken')
            ->willReturn($this->faker->uuid);

        $this->client->method('get')
            ->with($this->matchesRegularExpression('/v1\/shop-identities\/[^\/]+\/status$/'))
            ->willReturn($this->createResponse([
                "msg" => "Invalid request",
            ], 400));

        $cachedStatus = $this->statusManager->getStatus();

        //$this->assertNull($cachedStatus->cloudShopId);
        $this->assertFalse($cachedStatus->isVerified);
        $this->assertNull($cachedStatus->pointOfContactEmail);
    }

    /**
     * @test
     */
    public function itShouldNotUpdateStatusFromCloudOnError()
    {
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->faker->uuid,
            ]),
        ]))->toArray()));

        $this->shopSession->method('getValidToken')
            ->willReturn($this->faker->uuid);

        $this->client->method('get')
            ->with($this->matchesRegularExpression('/v1\/shop-identities\/[^\/]+\/status$/'))
            ->willReturn($this->createResponse([
                "msg" => "Invalid request",
            ], 400, true));

        sleep(1);

        $cachedStatus = $this->statusManager->getStatus(false, 1);

        //$this->assertNull($cachedStatus->cloudShopId);
        $this->assertFalse($cachedStatus->isVerified);
        $this->assertNull($cachedStatus->pointOfContactEmail);
    }

    /**
     * @test
     */
    public function itShouldCallOnBeforeStatusUpsertListenerWithCurrentAndNewStatus()
    {
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->faker->uuid,
                'isVerified' => false,
            ]),
        ]))->toArray()));

        $received = [];
        $this->statusManager->addOnBeforeStatusUpsert(function ($current, $new) use (&$received) {
            $received[] = ['current' => $current, 'new' => $new];
        });

        $this->statusManager->setIsVerified(true);

        $this->assertCount(1, $received);
        $this->assertInstanceOf(ShopStatus::class, $received[0]['current']);
        $this->assertInstanceOf(ShopStatus::class, $received[0]['new']);
        $this->assertFalse($received[0]['current']->isVerified);
        $this->assertTrue($received[0]['new']->isVerified);
    }

    /**
     * @test
     */
    public function itShouldPassNullCurrentOnFirstStatusUpsert()
    {
        $this->configurationRepository->updateCachedShopStatus(null);

        $received = [];
        $this->statusManager->addOnBeforeStatusUpsert(function ($current, $new) use (&$received) {
            $received[] = ['current' => $current, 'new' => $new];
        });

        $cloudShopId = $this->faker->uuid;
        $this->statusManager->setCloudShopId($cloudShopId);

        $this->assertCount(1, $received);
        $this->assertNull($received[0]['current']);
        $this->assertInstanceOf(ShopStatus::class, $received[0]['new']);
        $this->assertEquals($cloudShopId, $received[0]['new']->cloudShopId);
    }

    /**
     * @test
     */
    public function itShouldNotBreakUpsertWhenListenerThrows()
    {
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->faker->uuid,
                'isVerified' => false,
            ]),
        ]))->toArray()));

        $this->statusManager->addOnBeforeStatusUpsert(function ($current, $new) {
            throw new \RuntimeException('listener boom');
        });

        // must not propagate the listener exception
        $this->statusManager->setIsVerified(true);

        // and the status must still have been persisted
        $this->assertTrue($this->statusManager->getStatus(true)->isVerified);
    }

    /**
     * @test
     */
    public function itShouldNotRecurseWhenListenerTriggersAnotherUpsert()
    {
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->faker->uuid,
                'isVerified' => false,
            ]),
        ]))->toArray()));

        $calls = 0;
        $this->statusManager->addOnBeforeStatusUpsert(function ($current, $new) use (&$calls) {
            ++$calls;
            // re-entrant upsert: must be guarded, i.e. must not fire the listener again
            $this->statusManager->invalidateCache();
        });

        $this->statusManager->setIsVerified(true);

        $this->assertEquals(1, $calls);
    }
}

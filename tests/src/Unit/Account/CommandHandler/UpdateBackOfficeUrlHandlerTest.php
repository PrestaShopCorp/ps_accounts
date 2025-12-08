<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\Command\UpdateBackOfficeUrlCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpdateBackOfficeUrlHandler;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\ShopUrl;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\Module\PsAccounts\Context\ShopContext;

class UpdateBackOfficeUrlHandlerTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopContext
     */
    protected $shopContext;

    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopProvider;

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
     * @var Client&MockObject
     */
    public $client;

    /**
     * @var ShopSession&MockObject
     */
    public $shopSession;

    /**
     * @var int
     */
    protected $shopId = 1;

    public function set_up()
    {
        parent::set_up();

        $this->shopId = $this->shopProvider->getShopContext()->getContext()->shop->id;
        $this->client = $this->createMock(Client::class);
        $this->accountsService->setClient($this->client);
        $this->shopSession = $this->createMock(ShopSession::class);
    }

    /**
     * @test
     */
    public function itShouldupdateBackOfficeUrlWhenBackOfficeUrlChanged()
    {
        $cloudShopId = $this->faker->uuid;
        $token = $this->faker->uuid;

        $localShopUrl = $this->shopProvider->getUrl($this->shopId);
        $distantBackOfficeUrl = 'https://different-admin.example.com';

        $cachedShopStatus = new \PrestaShop\Module\PsAccounts\Account\CachedShopStatus([
            'isValid' => true,
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
                'isVerified' => true,
                'frontendUrl' => $localShopUrl->getFrontendUrl(),
                'backOfficeUrl' => $distantBackOfficeUrl,
            ])
        ]);

        $this->configurationRepository->updateCachedShopStatus(json_encode($cachedShopStatus->toArray()));

        $this->shopSession->method('getValidToken')->willReturn($token);

        $this->client->method('put')
            ->with(
                $this->matches('/v1/shop-identities/' . $cloudShopId . '/back-office-url'),
                $this->isType('array')
            )
            ->willReturnCallback(function ($route, $options) use ($cloudShopId, $token, $localShopUrl) {
                $this->assertEquals('Bearer ' . $token, $options[Request::HEADERS]['Authorization']);
                $this->assertEquals($localShopUrl->getBackOfficeUrl(), $options[Request::JSON]['backOfficeUrl']);
                $this->assertEquals($localShopUrl->getMultiShopId(), $options[Request::JSON]['multiShopId']);

                return $this->createResponse([
                    "success" => true,
                ], 200, true);
            });

        $this->getHandler()->handle(new UpdateBackOfficeUrlCommand($this->shopId));
    }

    /**
     * @test
     */
    public function itShouldNotupdateBackOfficeUrlWhenBackOfficeUrlNotChanged()
    {
        $cloudShopId = $this->faker->uuid;

        $localShopUrl = $this->shopProvider->getUrl($this->shopId);

        $cachedShopStatus = new \PrestaShop\Module\PsAccounts\Account\CachedShopStatus([
            'isValid' => true,
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
                'isVerified' => true,
                'frontendUrl' => $localShopUrl->getFrontendUrl(),
                'backOfficeUrl' => $localShopUrl->getBackOfficeUrl(),
            ])
        ]);

        $this->configurationRepository->updateCachedShopStatus(json_encode($cachedShopStatus->toArray()));

        $this->client->expects($this->never())->method('put');

        $this->getHandler()->handle(new UpdateBackOfficeUrlCommand($this->shopId));
    }

    /**
     * @return UpdateBackOfficeUrlHandler
     */
    private function getHandler()
    {
        return new UpdateBackOfficeUrlHandler(
            $this->shopContext,
            $this->commandBus,
            $this->accountsService,
            $this->statusManager,
            $this->shopProvider,
            $this->shopSession
        );
    }
}


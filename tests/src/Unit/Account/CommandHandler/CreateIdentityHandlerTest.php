<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\CreateIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class CreateIdentityHandlerTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopProvider;

    /**
     * @inject
     *
     * @var AccountsClient&MockObject
     */
    protected $accountsClient;

    /**
     * @var Oauth2Client&MockObject
     */
    protected $oauth2Client;

    /**
     * @var StatusManager&MockObject
     */
    public $shopStatus;

    /**
     * @var int
     */
    protected $shopId = 1;

    public function set_up()
    {
        parent::set_up();

        $this->shopId = $this->shopProvider->getShopContext()->getContext()->shop->id;

        $this->accountsClient = $this->createMock(AccountsClient::class);
        $this->oauth2Client = $this->createMock(Oauth2Client::class);
        $this->shopStatus = $this->createMock(StatusManager::class);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldStoreOauth2Client()
    {
        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;
        $cloudShopId = $this->faker->uuid;

        $this->oauth2Client->method('exists')->willReturn(false);
        $this->oauth2Client->method('update');

        $this->accountsClient
            ->method('createShopIdentity')
            ->willReturn($this->createApiResponse([
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                "cloudShopId" => $cloudShopId
            ], 200, true));

        $this->accountsClient
            ->expects($this->once())
            ->method('createShopIdentity');

        $this->shopStatus
            ->expects($this->once())
            ->method('setShopUuid')
            ->with($cloudShopId);

        $this->oauth2Client
            ->expects($this->once())
            ->method('exists');

        $this->oauth2Client
            ->expects($this->once())
            ->method('update')
            ->with($clientId, $clientSecret);

        $this->getCreateIdentityHandler()->handle(new CreateIdentityCommand(1, []));
    }


    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldNotUpdateExistingOAuth2Client()
    {
        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;
        $cloudShopId = $this->faker->uuid;

        $this->oauth2Client->method('exists')->willReturn(true);
        $this->oauth2Client->method('update');

        $this->shopStatus->method('exists')->willReturn(true);
        $this->shopStatus->method('update');

//        $this->accountsClient
//            ->method('createShopIdentity')
//            ->willReturn($this->createApiResponse([
//                'clientId' => $clientId,
//                'clientSecret' => $clientSecret,
//                "cloudShopId" => $cloudShopId
//            ], 200, true));

        $this->accountsClient
            ->expects($this->never())
            ->method('createShopIdentity');

        $this->shopStatus
            ->expects($this->never())
            ->method('setShopUuid');

        $this->oauth2Client
            ->expects($this->once())
            ->method('exists');

        $this->oauth2Client
            ->expects($this->never())
            ->method('update');

        $this->getCreateIdentityHandler()->handle(new CreateIdentityCommand(1, []));
    }

    /**
     * @return CreateIdentityHandler
     */
    private function getCreateIdentityHandler()
    {
        return new CreateIdentityHandler(
            $this->accountsClient,
            $this->shopProvider,
            $this->oauth2Client,
            $this->shopStatus
        );
    }
}

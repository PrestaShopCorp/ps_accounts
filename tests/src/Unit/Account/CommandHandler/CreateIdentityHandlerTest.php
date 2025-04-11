<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\CreateIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\ShopIdentity;
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
     * @var AccountsClient
     */
    protected $accountsClient;

    /**
     * @var Oauth2Client
     */
    protected $oauth2Client;

    /**
     * @var int
     */
    protected $shopId = 1;

    public function set_up()
    {
        parent::set_up();

        $this->accountsClient = $this->createMock(AccountsClient::class);

        $this->shopId = $this->shopProvider->getShopContext()->getContext()->shop->id;

        $this->oauth2Client = $this->createMock(Oauth2Client::class);

        $this->shopIdentity = $this->createMock(ShopIdentity::class);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldCreateAOauth2ClientIfNoneExists()
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
    public function itShouldNotCreateAOauth2ClientIfExists()
    {
        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;
        $cloudShopId = $this->faker->uuid;

        $this->oauth2Client->method('exists')->willReturn(true);
        $this->oauth2Client->method('update');

        $this->shopIdentity->method('exists')->willReturn(true);
        $this->shopIdentity->method('update');

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

        $this->oauth2Client
            ->expects($this->once())
            ->method('exists');
        $this->oauth2Client
            ->expects($this->never())
            ->method('update');

        $this->getCreateIdentityHandler()->handle(new CreateIdentityCommand(1, []));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldStoreIdentity()
    {
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
            $this->shopIdentity
        );
    }
}

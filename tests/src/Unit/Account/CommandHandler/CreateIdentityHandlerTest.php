<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\CreateIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
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
     * @var AccountsService
     */
    protected $accountsService;

    /**
     * @inject
     *
     * @var Oauth2Client
     */
    protected $oauth2Client;

    /**
     * @inject
     *
     * @var StatusManager
     */
    public $statusManager;

    /**
     * @inject
     *
     * @var ProofManager
     */
    public $proofManager;

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

        $this->shopId = $this->shopProvider->getShopContext()->getContext()->shop->id;
        $this->client = $this->createMock(Client::class);
        $this->accountsService->setClient($this->client);
    }

    /**
     * @test
     */
    public function itShouldStoreIdentity()
    {
        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;
        $cloudShopId = $this->faker->uuid;

        $this->oauth2Client->delete();
        $this->statusManager->setCloudShopId('');

        $this->client->method('post')
            ->willReturnCallback(function ($route) use ($clientId, $clientSecret, $cloudShopId) {
                if (preg_match('/v1\/shop-identities$/', $route)) {
                    return $this->createResponse([
                        'clientId' => $clientId,
                        'clientSecret' => $clientSecret,
                        "cloudShopId" => $cloudShopId
                    ], 200, true);
                }
                return $this->createResponse([], 500, true);
            });

        $this->getHandler()->handle(new CreateIdentityCommand(1, []));

        $this->assertEquals($cloudShopId, $this->statusManager->getCloudShopId());
        $this->assertEquals($clientId, $this->oauth2Client->getClientId());
        $this->assertEquals($clientSecret, $this->oauth2Client->getClientSecret());
    }

    /**
     * @test
     */
    public function itShouldNotChangeIdentityIfExists()
    {
        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;
        $cloudShopId = $this->faker->uuid;

        $id1 =$this->createResponse([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            "cloudShopId" => $cloudShopId
        ], 200, true);

        $id2 =$this->createResponse([
            'clientId' => $clientId . 'Foo',
            'clientSecret' => $clientSecret . 'Bar',
            "cloudShopId" => $cloudShopId . 'Baz'
        ], 200, true);

        $this->client
            ->method('post')
            ->willReturnCallback(function ($route) use ($id1, $id2) {
                static $count = 1;
                if (preg_match('/v1\/shop-identities$/', $route)) {
                    return $count++ === 1 ? $id1 : $id2;
                }
                return $this->createApiResponse([], 500, true);
            });

        $this->oauth2Client->delete();
        $this->statusManager->setCloudShopId('');

        $this->getHandler()->handle(new CreateIdentityCommand(1, []));

//        $this->oauth2Client->delete();
//        $this->statusManager->setCloudShopId('');

        $this->getHandler()->handle(new CreateIdentityCommand(1, []));

        $this->assertEquals($cloudShopId, $this->statusManager->getCloudShopId());
        $this->assertEquals($clientId, $this->oauth2Client->getClientId());
        $this->assertEquals($clientSecret, $this->oauth2Client->getClientSecret());
    }

    /**
     * @return CreateIdentityHandler
     */
    private function getHandler()
    {
        return new CreateIdentityHandler(
            $this->accountsService,
            $this->shopProvider,
            $this->oauth2Client,
            $this->statusManager,
            $this->proofManager
        );
    }
}

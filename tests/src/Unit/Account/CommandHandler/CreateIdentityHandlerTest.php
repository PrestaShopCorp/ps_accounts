<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\CachedShopStatus;
use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\Command\VerifyIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\CreateIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\VerifyIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
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
     * @var VerifyIdentityHandler&MockObject
     */
    public $verifyIdentityHandler;

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

        $this->verifyIdentityHandler = $this->createMock(VerifyIdentityHandler::class);
        $this->module->getServiceContainer()->set(VerifyIdentityHandler::class, $this->verifyIdentityHandler);
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
            ->with($this->matchesRegularExpression('/v1\/shop-identities$/'))
            ->willReturnCallback(function ($route, $options) use ($clientId, $clientSecret, $cloudShopId) {

                $this->assertArrayHasKey('backOfficeUrl', $options[Request::JSON]);
                $this->assertArrayHasKey('frontendUrl', $options[Request::JSON]);
                $this->assertArrayHasKey('multiShopId', $options[Request::JSON]);
                $this->assertEquals($this->proofManager->getProof(), $options[Request::JSON]['proof']);

                return $this->createResponse([
                    'clientId' => $clientId,
                    'clientSecret' => $clientSecret,
                    "cloudShopId" => $cloudShopId
                ], 200, true);
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

        $this->client->method('post')
            ->with($this->matchesRegularExpression('/v1\/shop-identities$/'))
            ->willReturnCallback(function ($route, $options) use ($id1, $id2, $cloudShopId) {

                $this->assertArrayHasKey('backOfficeUrl', $options[Request::JSON]);
                $this->assertArrayHasKey('frontendUrl', $options[Request::JSON]);
                $this->assertArrayHasKey('multiShopId', $options[Request::JSON]);
                $this->assertEquals($this->proofManager->getProof(), $options[Request::JSON]['proof']);

                static $count = 1;
                return $count++ === 1 ? $id1 : $id2;
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
     * @test
     */
    public function itShouldTriggerVerifyIdentityIfAlreadyCreated()
    {
        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;
        $cloudShopId = $this->faker->uuid;

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
                'isVerified' => false,
            ])
        ]))->toArray()));

        $this->oauth2Client->update($clientId, $clientSecret);

        $this->verifyIdentityHandler->expects($this->once())
            ->method('handle')
            ->with(
                $this->isInstanceOf(VerifyIdentityCommand::class)
            );

        $this->getHandler()->handle(new CreateIdentityCommand(1, []));
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
            $this->proofManager,
            $this->commandBus
        );
    }
}

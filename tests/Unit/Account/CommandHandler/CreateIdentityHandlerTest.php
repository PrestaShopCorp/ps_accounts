<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\CreateIdentityHandler;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class CreateIdentityHandlerTest extends TestCase
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

    public function setUp(): void
    {
        parent::setUp();

        $this->accountsClient = $this->createMock(AccountsClient::class);

        $this->shopId = $this->shopProvider->getShopContext()->getContext()->shop->id;

        $this->oauth2Client = $this->createMock(Oauth2Client::class);
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

        $this->oauth2Client->method('exist')->willReturn(false);
        $this->oauth2Client->method('update');

        $this->accountsClient
            ->method('createOauth2Client')
            ->willReturn($this->createApiResponse([
                'clientId' => $clientId,
                'clientSecret' => $clientSecret
            ], 200, true));

        $this->accountsClient
            ->expects($this->once())
            ->method('createOauth2Client');

        $this->oauth2Client
            ->expects($this->once())
            ->method('exist');
        $this->oauth2Client
            ->expects($this->once())
            ->method('createOauth2Client')
            ->with([$clientId, $clientSecret]);

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

        $this->oauth2Client->method('exist')->willReturn(true);
        $this->oauth2Client->method('update');

        $this->accountsClient
            ->method('createOauth2Client')
            ->willReturn($this->createApiResponse([
                'clientId' => $clientId,
                'clientSecret' => $clientSecret
            ], 200, true));

        $this->accountsClient
            ->expects($this->never())
            ->method('createOauth2Client');

        $this->oauth2Client
            ->expects($this->once())
            ->method('exist');
        $this->oauth2Client
            ->expects($this->never())
            ->method('createOauth2Client')
            ->with([$clientId, $clientSecret]);

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
            $this->shopContext,
            $this->linkShop
        );
    }
}

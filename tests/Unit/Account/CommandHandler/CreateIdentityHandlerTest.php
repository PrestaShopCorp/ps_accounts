<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\CreateIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\CreateIdentityHandler;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Identity\Domain\IdentityManager;
use PrestaShop\Module\PsAccounts\Identity\Infrastructure\ConfigurationIdentityManager;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
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
     * @var ConfigurationIdentityManager
     */
    protected $identityManager;

    /**
     * @var int
     */
    protected $shopId = 1;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountsClient = $this->createMock(AccountsClient::class);

        $this->shopId = $this->shopProvider->getShopContext()->getContext()->shop->id;

        $this->identityManager = $this->createMock(ConfigurationIdentityManager::class);
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

        $this->identityManager->method('get')->willReturn(false);
        $this->identityManager->method('save');

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

        $this->identityManager
            ->expects($this->once())
            ->method('get');
        $this->identityManager
            ->expects($this->once())
            ->method('save')
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

        $this->identityManager->method('get')->willReturn(true);
        $this->identityManager->method('save');

        $this->accountsClient
            ->method('createShopIdentity')
            ->willReturn($this->createApiResponse([
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                "cloudShopId" => $cloudShopId
            ], 200, true));

        $this->accountsClient
            ->expects($this->never())
            ->method('createShopIdentity');

        $this->identityManager
            ->expects($this->once())
            ->method('get');
        $this->identityManager
            ->expects($this->never())
            ->method('save');

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
            $this->identityManager
        );
    }
}

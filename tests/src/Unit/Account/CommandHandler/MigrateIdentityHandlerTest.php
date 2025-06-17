<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\Command\MigrateShopIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\MigrateShopIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class MigrateIdentityHandlerTest extends TestCase
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
     * @var OAuth2Service
     */
    protected $oAuth2Service;

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
    public $accountsClient;

    /**
     * @var Client&MockObject
     */
    public $oAuth2Client;

    /**
     * @var ShopSession&MockObject
     */
    public $shopSession;

    /**
     * @var int
     */
    protected $shopId = 1;

    /**
     * @var string
     */
    private $wellKnown = <<<JSON
{
    "authorization_endpoint": "https://oauth.foo.bar/oauth2/auth",
    "token_endpoint": "https://oauth.foo.bar/oauth2/token",
    "userinfo_endpoint": "https://oauth.foo.bar/userinfo",
    "jwks_uri": "https://oauth.foo.bar/.well-known/jwks.json"
}
JSON;

    /**
     * @var Response
     */
    protected $wellKnownResponse;

    public function set_up()
    {
        parent::set_up();

        $this->shopId = $this->shopProvider->getShopContext()->getContext()->shop->id;
        $this->accountsClient = $this->createMock(Client::class);
        $this->oAuth2Client = $this->createMock(Client::class);
        $this->accountsService->setClient($this->accountsClient);
        $this->oAuth2Service->setHttpClient($this->oAuth2Client);
        $this->shopSession = $this->createMock(ShopSession::class);
        $this->wellKnownResponse = $this->createResponse($this->wellKnown);
    }

    /**
     * @test
     */
    public function itShouldMigrateIdentityFromV7()
    {
        $cloudShopId = $this->faker->uuid;
        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;

        // preliminary check to require refresh token
        $this->oAuth2Service->getOAuth2Client()->update($clientId, $clientSecret);

        // introduced in v7
        $this->configurationRepository->updateLastUpgrade('7.2.0');

        $this->configurationRepository->updateShopUuid($cloudShopId);

        $this->oAuth2Client->method('get')
            ->willReturnCallback(function ($route) {
                if (preg_match('/openid-configuration/', $route)) {
                    return $this->wellKnownResponse;
                }
                return $this->createResponse([], 500, true);
            });

        $this->oAuth2Client->method('post')
            ->willReturnCallback(function ($route) {
                if (preg_match('@' . $this->oAuth2Service->getWellKnown()->token_endpoint . '@', $route)) {
                    return $this->createResponse([
                        'access_token' => $this->faker->uuid,
                    ], 200, true);
                }
                return $this->createResponse([], 500, true);
            });

        $this->accountsClient->method('put')
            ->willReturnCallback(function ($route) use ($cloudShopId, $clientId, $clientSecret) {
                if (preg_match('/v1\/shop-identities\/' . $cloudShopId . '\/migration$/', $route)) {
                    return $this->createResponse([
                        'clientId' => $clientId,
                        'clientSecret' => $clientSecret,
                        "cloudShopId" => $cloudShopId
                    ], 200, true);
                }
                return $this->createResponse([], 500, true);
            });

        $this->getHandler()->handle(new MigrateShopIdentityCommand($this->shopId, ''));

        $this->assertTrue($this->statusManager->cacheInvalidated());
        $this->assertEquals($cloudShopId, $this->statusManager->getCloudShopId());
        $this->assertEquals($clientId, $this->oAuth2Service->getOAuth2Client()->getClientId());
        $this->assertEquals($clientSecret, $this->oAuth2Service->getOAuth2Client()->getClientSecret());
    }

    /**
     * @test
     */
    public function itShouldMigrateIdentityFromV5AndV6()
    {
        $cloudShopId = $this->faker->uuid;
        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;

        //$this->oAuth2Service->getOAuth2Client()->update($clientId, $clientSecret);

        // introduced in v7
        $this->configurationRepository->updateLastUpgrade(null);

        $this->configurationRepository->updateShopUuid($cloudShopId);

        $this->accountsClient->method('post')
            ->willReturnCallback(function ($route) {
                if (preg_match('/v1\/shop\/token\/refresh/', $route)) {
                    return $this->createResponse([
                        'token' => $this->faker->uuid,
                        'refresh_token' => $this->faker->uuid,
                        'id_token' => $this->faker->uuid,
                    ], 200, true);
                }
                return $this->createResponse([], 500, true);
            });

        $this->accountsClient->method('put')
            ->willReturnCallback(function ($route) use ($cloudShopId, $clientId, $clientSecret) {
                if (preg_match('/v1\/shop-identities\/' . $cloudShopId . '\/migration$/', $route)) {
                    return $this->createResponse([
                        'clientId' => $clientId,
                        'clientSecret' => $clientSecret,
                        "cloudShopId" => $cloudShopId
                    ], 200, true);
                }
                return $this->createResponse([], 500, true);
            });

        $this->getHandler()->handle(new MigrateShopIdentityCommand($this->shopId, ''));

        $this->assertTrue($this->statusManager->cacheInvalidated());
        $this->assertEquals($cloudShopId, $this->statusManager->getCloudShopId());
        $this->assertEquals($clientId, $this->oAuth2Service->getOAuth2Client()->getClientId());
        $this->assertEquals($clientSecret, $this->oAuth2Service->getOAuth2Client()->getClientSecret());
    }

    /**
     * @test
     */
    public function itShouldNotMigrateOnError()
    {
        $cloudShopId = $this->faker->uuid;
        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;

        // preliminary check to require refresh token
        $this->oAuth2Service->getOAuth2Client()->update($clientId, $clientSecret);

        // introduced in v7
        $this->configurationRepository->updateLastUpgrade('7.2.0');

        $this->configurationRepository->updateShopUuid($cloudShopId);

        $this->oAuth2Client->method('get')
            ->willReturnCallback(function ($route) {
                if (preg_match('/openid-configuration/', $route)) {
                    return $this->wellKnownResponse;
                }
                return $this->createResponse([], 500, true);
            });

        $this->oAuth2Client->method('post')
            ->willReturnCallback(function ($route) {
                if (preg_match('@' . $this->oAuth2Service->getWellKnown()->token_endpoint . '@', $route)) {
                    return $this->createResponse([
                        'access_token' => $this->faker->uuid,
                    ], 200, true);
                }
                return $this->createResponse([], 500, true);
            });

        $this->accountsClient->method('put')
            ->willReturnCallback(function ($route) use ($cloudShopId, $clientId, $clientSecret) {
                if (preg_match('/v1\/shop-identities\/' . $cloudShopId . '\/migration$/', $route)) {
                    return $this->createResponse([
                       "error" => 'store-identity/migration-failed',
                       "message" => 'Cannot migrate shop',
                    ], 400, true);
                }
                return $this->createResponse([], 500, true);
            });

        $this->getHandler()->handle(new MigrateShopIdentityCommand($this->shopId, ''));

        $this->assertTrue($this->statusManager->cacheInvalidated());
        $this->assertEmpty($this->statusManager->getCloudShopId());
    }

    /**
     * @return MigrateShopIdentityHandler
     */
    private function getHandler()
    {
        return new MigrateShopIdentityHandler(
            $this->accountsService,
            $this->shopProvider,
            $this->statusManager,
            $this->configurationRepository,
            $this->oAuth2Service
        );
    }
}

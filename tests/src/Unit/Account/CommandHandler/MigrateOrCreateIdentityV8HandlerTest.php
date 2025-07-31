<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\Command\MigrateOrCreateIdentityV8Command;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\MigrateOrCreateIdentityV8Handler;
use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\UpgradeService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class MigrateOrCreateIdentityV8HandlerTest extends TestCase
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
     * @var Client&MockObject
     */
    public $accountsClient;

    /**
     * @var Client&MockObject
     */
    public $oAuth2Client;

    /**
     * @inject
     *
     * @var StatusManager
     */
    public $statusManager;

    /**
     * @inject
     *
     * @var UpgradeService
     */
    public $upgradeService;

    /**
     * @inject
     *
     * @var ProofManager
     */
    public $proofManager;

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

//        $this->accountsService = $this->createMock(AccountsService::class);
//        $this->oAuth2Service = $this->createMock(OAuth2Service::class);

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
        $token = $this->faker->uuid;
        $tokenAudience = 'shop_' . $cloudShopId;

        // introduced in v7
        //$this->configurationRepository->updateLastUpgrade('7.2.0');
        $this->upgradeService->setVersion('7.2.0');

        $this->configurationRepository->updateShopUuid($cloudShopId);

        // FIXME: test OAuth2Service in a dedicated Class
        // preliminary check to require refresh token
        $this->oAuth2Service->getOAuth2Client()->update($clientId, $clientSecret);

        $this->oAuth2Client->method('get')
            ->with($this->matchesRegularExpression('/openid-configuration/'))
            ->willReturn($this->wellKnownResponse);

        $this->oAuth2Client->method('post')
            ->with($this->matchesRegularExpression('@' . $this->oAuth2Service->getWellKnown()->token_endpoint . '@'))
            ->willReturnCallback(function ($route, $options) use ($cloudShopId, $clientId, $clientSecret, $token, $tokenAudience) {

                $this->assertTrue((bool) preg_match('/' . $tokenAudience . '/', $options[Request::FORM]['audience']));

                return $this->createResponse([
                    'access_token' => $token,
                ], 200, true);
            });

        // FIXME: test AccountsClient in a dedicated Class
        $this->accountsClient->method('put')
            ->with(
                $this->matches('/v1/shop-identities/' . $cloudShopId . '/migrate'),
                $this->isType('array')
            )
            ->willReturnCallback(function ($route, $options) use ($cloudShopId, $clientId, $clientSecret, $token) {

                $this->assertEquals('Bearer ' . $token, $options[Request::HEADERS]['Authorization']);
                $this->assertArrayHasKey('backOfficeUrl', $options[Request::JSON]);
                $this->assertArrayHasKey('frontendUrl', $options[Request::JSON]);
                $this->assertArrayHasKey('multiShopId', $options[Request::JSON]);
                $this->assertEquals($this->proofManager->getProof(), $options[Request::JSON]['proof']);
                //$this->assertEquals((string) $this->configurationRepository->getLastUpgrade(), $options[Request::JSON]['fromVersion']);
                $this->assertEquals((string) $this->upgradeService->getVersion(), $options[Request::JSON]['fromVersion']);

                return $this->createResponse([
                    'clientId' => $clientId,
                    'clientSecret' => $clientSecret,
                    "cloudShopId" => $cloudShopId
                ], 200, true);
            });

        $this->getHandler()->handle(new MigrateOrCreateIdentityV8Command($this->shopId));

        $this->assertEquals(\Ps_accounts::VERSION, $this->upgradeService->getVersion());
        $this->assertEmpty($this->configurationRepository->getAccessToken());
        $this->assertTrue($this->statusManager->cacheInvalidated());
        $this->assertEquals($cloudShopId, $this->statusManager->getCloudShopId());
        $this->assertEquals($clientId, $this->oAuth2Service->getOAuth2Client()->getClientId());
        $this->assertEquals($clientSecret, $this->oAuth2Service->getOAuth2Client()->getClientSecret());
    }

//    /**
//     * @test
//     */
//    public function itShouldMigrateIdentityFromV7()
//    {
//        $cloudShopId = $this->faker->uuid;
//        $clientId = $this->faker->uuid;
//        $clientSecret = $this->faker->uuid;
//
//        // introduced in v7
//        $this->configurationRepository->updateLastUpgrade('7.2.0');
//
//        $this->configurationRepository->updateShopUuid($cloudShopId);
//
//        /** @var OAuth2Client&MockObject $oAuth2Client */
//        $oAuth2Client = $this->createMock(OAuth2Client::class);
//
//        $this->oAuth2Service
//            ->method('getOAuth2Client')
//            ->willReturn($oAuth2Client);
//
//        $accessToken = new AccessToken([
//            'access_token' => $this->faker->uuid,
//        ]);
//
//        $identityCreated = new IdentityCreated([
//            'cloudShopId' => $cloudShopId,
//            'clientId' => $clientId,
//            'clientSecret' => $clientSecret,
//        ]);
//
//        $this->oAuth2Service
//            ->expects($this->once())
//            ->method('getAccessTokenByClientCredentials')
//            ->willReturn($accessToken);
//
//        $this->accountsService
//            ->expects($this->once())
//            ->method('migrateShopIdentity')
//            ->with(
//                $this->equalTo($cloudShopId),
//                $this->equalTo($accessToken->access_token),
//                $this->isInstanceOf(ShopUrl::class),
//                $this->isType('string')
//            )
//            ->willReturn($identityCreated);
//
//        $oAuth2Client
//            ->expects($this->once())
//            ->method('update')
//            ->with($clientId, $clientSecret);
//
//        $this->getHandler()->handle(new MigrateShopIdentityCommand($this->shopId, ''));
//
//        $this->assertEmpty($this->configurationRepository->getAccessToken());
//        $this->assertTrue($this->statusManager->cacheInvalidated());
//        $this->assertEquals($cloudShopId, $this->statusManager->getCloudShopId());
//    }

    /**
     * @test
     */
    public function itShouldMigrateIdentityFromV5AndV6()
    {
        $cloudShopId = $this->faker->uuid;
        $clientId = $this->faker->uuid;
        $clientSecret = $this->faker->uuid;
        $token = $this->faker->uuid;

        // introduced in v7
        //$this->configurationRepository->updateLastUpgrade(null);

        $fromVersion = '5.6.2';
        \Db::getInstance()->execute(
            'UPDATE ' . _DB_PREFIX_ . 'module SET version = \'' . $fromVersion . '\' WHERE name = \'' . UpgradeService::MODULE_NAME . '\''
        );
        // FIXME: not working with null on v9
        $this->upgradeService->setVersion('0');

        $this->configurationRepository->updateShopUuid($cloudShopId);

        // FIXME: test AccountsClient in a dedicated Class
        $this->accountsClient->method('post')
            ->with($this->matchesRegularExpression('/v1\/shop\/token\/refresh/'))
            ->willReturnCallback(function ($route, $options) use ($token) {
                return $this->createResponse([
                    'token' => $token,
                    'refresh_token' => $this->faker->uuid,
                    'id_token' => $this->faker->uuid,
                ], 200, true);
            });

        // FIXME: test AccountsClient in a dedicated Class
        $this->accountsClient->method('put')
            ->with(
                $this->matches('/v1/shop-identities/' . $cloudShopId . '/migrate'),
                $this->isType('array')
            )
            ->willReturnCallback(function ($route, $options) use ($cloudShopId, $clientId, $clientSecret, $token, $fromVersion) {

                $this->assertEquals('Bearer ' . $token, $options[Request::HEADERS]['Authorization']);
                $this->assertArrayHasKey('backOfficeUrl', $options[Request::JSON]);
                $this->assertArrayHasKey('frontendUrl', $options[Request::JSON]);
                $this->assertArrayHasKey('multiShopId', $options[Request::JSON]);
                $this->assertEquals($this->proofManager->getProof(), $options[Request::JSON]['proof']);
                //$this->assertEquals((string) $this->configurationRepository->getLastUpgrade(), $options[Request::JSON]['fromVersion']);
                $this->assertEquals($fromVersion, $options[Request::JSON]['fromVersion']);

                return $this->createResponse([
                    'clientId' => $clientId,
                    'clientSecret' => $clientSecret,
                    "cloudShopId" => $cloudShopId
                ], 200, true);
            });

        $this->getHandler()->handle(new MigrateOrCreateIdentityV8Command($this->shopId));

        $this->assertEquals(\Ps_accounts::VERSION, $this->upgradeService->getVersion());
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
        $token =  $this->faker->uuid;
        $tokenAudience = 'shop_' . $cloudShopId;

        // introduced in v7
        //$this->configurationRepository->updateLastUpgrade('7.2.0');
        $fromVersion = '7.2.0';
        $this->upgradeService->setVersion($fromVersion);

        $this->configurationRepository->updateShopUuid($cloudShopId);

        // FIXME: test OAuth2Service in a dedicated Class
        // preliminary check to require refresh token
        $this->oAuth2Service->getOAuth2Client()->update($clientId, $clientSecret);

        $this->oAuth2Client->method('get')
            ->with($this->matchesRegularExpression('/openid-configuration/'))
            ->willReturn($this->wellKnownResponse);

        $this->oAuth2Client->method('post')
            ->with($this->matchesRegularExpression('@' . $this->oAuth2Service->getWellKnown()->token_endpoint . '@'))
            ->willReturnCallback(function ($route, $options) use ($cloudShopId, $clientId, $clientSecret, $token, $tokenAudience) {

                $this->assertTrue((bool) preg_match('/' . $tokenAudience . '/', $options[Request::FORM]['audience']));

                return $this->createResponse([
                    'access_token' => $token,
                ], 200, true);
            });

        // FIXME: test AccountsClient in a dedicated Class
        $this->accountsClient->method('put')
            ->with(
                $this->matches('/v1/shop-identities/' . $cloudShopId . '/migrate'),
                $this->isType('array')
            )
            ->willReturnCallback(function ($route, $options) use ($cloudShopId, $clientId, $clientSecret, $token) {

                $this->assertEquals('Bearer ' . $token, $options[Request::HEADERS]['Authorization']);
                $this->assertArrayHasKey('backOfficeUrl', $options[Request::JSON]);
                $this->assertArrayHasKey('frontendUrl', $options[Request::JSON]);
                $this->assertArrayHasKey('multiShopId', $options[Request::JSON]);
                $this->assertEquals($this->proofManager->getProof(), $options[Request::JSON]['proof']);
                //$this->assertEquals((string) $this->configurationRepository->getLastUpgrade(), $options[Request::JSON]['fromVersion']);
                $this->assertEquals((string) $this->upgradeService->getVersion(), $options[Request::JSON]['fromVersion']);

                return $this->createResponse([
                    "error" => 'store-identity/migration-failed',
                    "message" => 'Cannot migrate shop',
                ], 400, true);
            });

        $this->getHandler()->handle(new MigrateOrCreateIdentityV8Command($this->shopId));

        $this->assertEquals($fromVersion, $this->upgradeService->getVersion());
        $this->assertTrue($this->statusManager->cacheInvalidated());
        $this->assertEquals($cloudShopId, $this->statusManager->getCloudShopId());

        // FIXME: test something relevant
    }

    /**
     * @return MigrateOrCreateIdentityV8Handler
     */
    private function getHandler()
    {
        return new MigrateOrCreateIdentityV8Handler(
            $this->accountsService,
            $this->oAuth2Service,
            $this->shopProvider,
            $this->statusManager,
            $this->proofManager,
            $this->configurationRepository,
            $this->commandBus,
            $this->upgradeService
        );
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\UpgradeModuleCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpgradeModuleHandler;
use PrestaShop\Module\PsAccounts\Account\Dto\UpgradeModule;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class UpgradeModuleHandlerTest extends TestCase
{
    /**
     * @inject
     *
     * @var LinkShop
     */
    protected $linkShop;

    /**
     * @inject
     *
     * @var ShopSession
     */
    protected $shopSession;

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
     * @inject
     *
     * @var AnalyticsService
     */
    protected $analyticsService;

    public function setUp()
    {
        parent::setUp();

        $this->firebaseToken = new Token((string) $this->makeJwtToken(new \DateTimeImmutable()), 'not-fresh');
        $this->firebaseRefreshedToken = new Token((string) $this->makeJwtToken(new \DateTimeImmutable('+1hour')), 'not-fresh');

        $this->accountsClient = $this->createMock(AccountsClient::class);
        $this->accountsClient
            ->method('upgradeShopModule')
            ->willReturn($this->createApiResponse([], 200, true));
        $this->accountsClient
            ->method('refreshShopToken')
            ->with($this->firebaseToken->getRefreshToken(), $this->linkShop->getShopUuid())
            ->willReturn($this->createApiResponse([
                'token' => (string) $this->firebaseRefreshedToken->getJwt(),
                'refresh_token' => $this->firebaseRefreshedToken->getRefreshToken(),
            ], 200, true));

        $this->analyticsService = $this->createMock(AnalyticsService::class);

        $this->shopSession = $this->createMock(ShopSession::class);
        $this->shopSession->method('getOrRefreshToken')->willReturn($this->firebaseRefreshedToken);
        $this->shopSession->method('getToken')->willReturn($this->firebaseToken);

        $this->conf = $this->createMock(ConfigurationRepository::class);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldUpgradePrevModuleVersions()
    {
        $currentVersion = '6.3.2';
        $upgradeVersion = '7.0.0';

        $this->conf->method('getLastUpgrade')->willReturn($currentVersion);

        $handler = new UpgradeModuleHandler(
            $this->accountsClient,
            $this->linkShop,
            $this->shopSession,
            $this->shopProvider,
            $this->conf,
            $this->analyticsService
        );

        $this->accountsClient
            ->expects($this->once())
            ->method('upgradeShopModule');

        $this->conf
            ->expects($this->once())
            ->method('setLastUpgrade')
            ->with($upgradeVersion);

        $handler->handle(new UpgradeModuleCommand(new UpgradeModule([
            'version' => $upgradeVersion,
            'shopId' => null,
        ])));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldNotUpgradeNextModuleVersions()
    {
        $currentVersion = '7.0.1';
        $upgradeVersion = '7.0.0';

        $this->conf->method('getLastUpgrade')->willReturn($currentVersion);

        $handler = new UpgradeModuleHandler(
            $this->accountsClient,
            $this->linkShop,
            $this->shopSession,
            $this->shopProvider,
            $this->conf,
            $this->analyticsService
        );

        $this->accountsClient
            ->expects($this->exactly(0))
            ->method('upgradeShopModule');

        $handler->handle(new UpgradeModuleCommand(new UpgradeModule([
            'version' => $upgradeVersion,
            'shopId' => null,
        ])));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldAttemptToRefreshTokenWithFirebaseRefreshToken()
    {
        $currentVersion = '6.3.2';
        $upgradeVersion = '7.0.0';

        $this->conf->method('getLastUpgrade')->willReturn($currentVersion);

        $handler = new UpgradeModuleHandler(
            $this->accountsClient,
            $this->linkShop,
            $this->shopSession,
            $this->shopProvider,
            $this->conf,
            $this->analyticsService
        );

        $this->accountsClient
            ->expects($this->once())
            ->method('upgradeShopModule');
        $this->accountsClient
            ->expects($this->once())
            ->method('refreshShopToken');

        $this->conf
            ->expects($this->once())
            ->method('setLastUpgrade')
            ->with($upgradeVersion);

        $this->shopSession
            ->expects($this->once())
            ->method('setToken')
            ->with((string) $this->firebaseRefreshedToken->getJwt(), $this->firebaseRefreshedToken->getRefreshToken());

        $handler->handle(new UpgradeModuleCommand(new UpgradeModule([
            'version' => $upgradeVersion,
            'shopId' => null,
        ])));
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldTriggerSegmentEventOnFailure()
    {
        $currentVersion = '6.3.2';
        $upgradeVersion = '7.0.0';

        $this->conf->method('getLastUpgrade')->willReturn($currentVersion);

        $this->accountsClient = $this->createMock(AccountsClient::class);
        $this->accountsClient
            ->method('upgradeShopModule')
            ->willReturn($this->createApiResponse([
                'message' => 'Failed upgrading module',
            ], 500, false));

        $handler = new UpgradeModuleHandler(
            $this->accountsClient,
            $this->linkShop,
            $this->shopSession,
            $this->shopProvider,
            $this->conf,
            $this->analyticsService
        );

        $shop = $this->shopProvider->formatShopData((array) \Shop::getShop(
            $this->shopProvider->getShopContext()->getContext()->shop->id)
        );
        $this->analyticsService
            ->expects($this->once())
            ->method('trackShopUnlinkedOnError')
            ->with(
                $this->linkShop->getOwnerUuid(),
                $this->linkShop->getOwnerEmail(),
                $this->linkShop->getShopUuid(),
                $shop->frontUrl,
                $shop->url,
                'ps_accounts',
                500
            );

        $handler->handle(new UpgradeModuleCommand(new UpgradeModule([
            'version' => $upgradeVersion,
            'shopId' => null,
        ])));
    }


    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldUnlinkOnFailure()
    {
        $currentVersion = '6.3.2';
        $upgradeVersion = '7.0.0';

        $this->conf->method('getLastUpgrade')->willReturn($currentVersion);

        $this->accountsClient = $this->createMock(AccountsClient::class);
        $this->accountsClient
            ->method('upgradeShopModule')
            ->willReturn($this->createApiResponse([
                'message' => 'Failed upgrading module',
            ], 500, false));

        $this->linkShop = $this->createMock(LinkShop::class);
        $this->linkShop
            ->expects($this->once())
            ->method('delete');

        $handler = new UpgradeModuleHandler(
            $this->accountsClient,
            $this->linkShop,
            $this->shopSession,
            $this->shopProvider,
            $this->conf,
            $this->analyticsService
        );

        $handler->handle(new UpgradeModuleCommand(new UpgradeModule([
            'version' => $upgradeVersion,
            'shopId' => null,
        ])));
    }
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldNotAttemptToRefreshTokenWithFirebaseRefreshToken()
    {
        $this->markTestSkipped('Not needed as long as we maintain refresh tokens for billing');

        $currentVersion = '7.3.2';
        $upgradeVersion = '8.0.0';

        $this->conf->method('getLastUpgrade')->willReturn($currentVersion);

        $handler = new UpgradeModuleHandler(
            $this->accountsClient,
            $this->linkShop,
            $this->shopSession,
            $this->shopProvider,
            $this->conf,
            $this->analyticsService
        );

        $this->accountsClient
            ->expects($this->once())
            ->method('upgradeShopModule');
        $this->accountsClient
            ->expects($this->exactly(0))
            ->method('refreshShopToken');

        $this->conf
            ->expects($this->once())
            ->method('setLastUpgrade')
            ->with($upgradeVersion);

        $this->shopSession
            ->expects($this->once())
            ->method('setToken')
            ->with((string) $this->firebaseRefreshedToken->getJwt(), $this->firebaseRefreshedToken->getRefreshToken());

        $handler->handle(new UpgradeModuleCommand(new UpgradeModule([
            'version' => $upgradeVersion,
            'shopId' => null,
        ])));
    }

    public function itShouldDealWithConcurrentRequests()
    {
        // TODO implement test
    }
}

<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\UpgradeModuleCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpgradeModuleHandler;
use PrestaShop\Module\PsAccounts\Account\Dto\UpgradeModule;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
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
     * @var ShopContext
     */
    protected $shopContext;

    /**
     * @inject
     *
     * @var AccountsClient
     */
    protected $accountsClient;

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

//        $accountsClient = $this->createMock(AccountsClient::class);
//        $accountsClient
//            ->expects($this->once())
//            ->method('upgradeShopModule')->willReturn($this->createApiResponse([
//                // FIXME: empty response
//            ], 200, true));
//        $accountsClient->method('refreshShopToken')->willReturn($this->createApiResponse([
//            'token' => (string) $this->makeJwtToken(new \DateTimeImmutable()),
//            'refresh_token' => 'not-fresh',
//        ], 200, true));

        $this->accountsClient = $this->createMockWithMethods(AccountsClient::class, [
            'upgradeShopModule' => $this->createApiResponse([
                // FIXME: empty response
            ], 200, true),
            'refreshShopToken' => $this->createApiResponse([
                'token' => (string) $this->makeJwtToken(new \DateTimeImmutable()),
                'refresh_token' => 'not-fresh',
            ], 200, true),
        ]);

        $token = new Token((string) $this->makeJwtToken(new \DateTimeImmutable()), 'not-fresh');
        $this->shopSession = $this->createMockWithMethods(ShopSession::class, [
            'getOrRefreshToken' => $token,
            'getToken' => $token,
        ]);

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

        $handler = new UpgradeModuleHandler(
            $this->accountsClient,
            $this->linkShop,
            $this->shopSession,
            $this->shopContext,
            $this->conf
        );

        $this->accountsClient->expects($this->once())->method('upgradeShopModule');
        $this->conf->method('getLastUpgrade')->willReturn($currentVersion);
        $this->conf->expects($this->once())->method('setLastUpgrade')->with($upgradeVersion);

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

        $handler = new UpgradeModuleHandler(
            $this->accountsClient,
            $this->linkShop,
            $this->shopSession,
            $this->shopContext,
            $this->conf
        );

        $this->accountsClient->expects($this->exactly(0))->method('upgradeShopModule');
        $this->conf->method('getLastUpgrade')->willReturn($currentVersion);

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

        $token = new Token((string) $this->makeJwtToken(new \DateTimeImmutable()), 'not-fresh');
        $this->shopSession = $this->createMockWithMethods(ShopSession::class, [
            'getOrRefreshToken' => $token,
            'getToken' => $token,
        ]);

        $handler = new UpgradeModuleHandler(
            $this->accountsClient,
            $this->linkShop,
            $this->shopSession,
            $this->shopContext,
            $this->conf
        );

        $this->accountsClient->expects($this->once())->method('upgradeShopModule');
        $this->conf->method('getLastUpgrade')->willReturn($currentVersion);
        $this->conf->expects($this->once())->method('setLastUpgrade')
            ->with($upgradeVersion);

        $this->accountsClient->expects($this->once())->method('refreshShopToken')
            ->with($token->getRefreshToken(), $this->linkShop->getShopUuid());

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
        $currentVersion = '7.3.2';
        $upgradeVersion = '8.0.0';

        $token = new Token((string) $this->makeJwtToken(new \DateTimeImmutable()), 'not-fresh');
        $this->shopSession = $this->createMockWithMethods(ShopSession::class, [
            'getOrRefreshToken' => $token,
            'getToken' => $token,
        ]);

        $handler = new UpgradeModuleHandler(
            $this->accountsClient,
            $this->linkShop,
            $this->shopSession,
            $this->shopContext,
            $this->conf
        );

        $this->accountsClient->expects($this->once())->method('upgradeShopModule');
        $this->conf->method('getLastUpgrade')->willReturn($currentVersion);
        $this->conf->expects($this->once())->method('setLastUpgrade')
            ->with($upgradeVersion);

        // Should not attempt to use firebase refreshToken after 7.0.0
        $this->accountsClient->expects($this->exactly(0))->method('refreshShopToken')
            ->with($token->getRefreshToken(), $this->linkShop->getShopUuid());

        $handler->handle(new UpgradeModuleCommand(new UpgradeModule([
            'version' => $upgradeVersion,
            'shopId' => null,
        ])));
    }

    public function itShouldNotFailIfTokenCantBeRefreshed()
    {
        // TODO implement test
    }

    public function itShouldDealWithConcurrentRequests()
    {
        // TODO implement test
    }
}

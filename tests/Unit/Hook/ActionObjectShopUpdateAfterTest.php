<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Hook;

use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpdateUserShopHandler;
use PrestaShop\Module\PsAccounts\Account\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\PrestaShop\Adapter\Meta\ShopUrlDataConfiguration;

class Params {
    /** @var string */
    public $ownerUid;
    /** @var string */
    public $shopUid;
    /** @var string */
    public $ownerToken;
    /** @var UpdateShop */
    public $shop;
};

class ActionObjectShopUpdateAfterTest extends TestCase
{
    /**
     * @inject
     *
     * @var UpdateUserShopHandler
     */
    protected $updateUserShopHandler;

    /**
     * @inject
     *
     * @var AccountsClient
     */
    protected $accountClient;

    /**
     * @inject
     *
     * @var Firebase\ShopSession
     */
    protected $shopSession;

    /**
     * @inject
     *
     * @var Firebase\OwnerSession
     */
    protected $ownerSession;

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->replaceProperty($this->updateUserShopHandler, 'accountClient', $this->accountClient);
        $this->replaceProperty($this->updateUserShopHandler, 'shopSession', $this->shopSession);
        $this->replaceProperty($this->updateUserShopHandler, 'ownerSession', $this->ownerSession);
    }

    /**
     * @test
     */
    public function itShouldAttemptToUpdateShop()
    {
        $shop = new \Shop(1);

        /** @var Params $params */
        $params = null;

        $shopToken = new Token((string)$this->makeJwtToken(new \DateTimeImmutable('+1 hour'), [
            'sub' => $this->faker->uuid,
        ]));
        $ownerToken = new Token((string)$this->makeJwtToken(new \DateTimeImmutable('+1 hour'), [
            'sub' => $this->faker->uuid
        ]));
        $updateUserShopResponse = $this->createApiResponse([
            'message' => 'all good !',
        ], 200, true);

        $this->initResponse($params, $updateUserShopResponse);
        $this->initTokens($shopToken, $ownerToken);

        $newName = $this->faker->slug();

        $shop->name = $newName;
        $shop->update();

        $this->module->getLogger()->info(json_encode($params));

        // FIXME: test data exhaustively
        $this->assertEquals($shop->id, $params->shop->shopId);
        $this->assertEquals($newName, $params->shop->name);
        $this->assertEquals('http://' . $shop->domain, $params->shop->domain);
        $this->assertEquals('https://' . $shop->domain_ssl, $params->shop->sslDomain);
        $this->assertEquals((string) $ownerToken, $params->ownerToken);
        $this->assertEquals($shopToken->getJwt()->claims()->get('sub'), $params->shopUid);
        $this->assertEquals($ownerToken->getJwt()->claims()->get('sub'), $params->ownerUid);
    }

    /**
     * @test
     */
    public function itShouldAttemptToUpdateShopOnUrlUpdate()
    {
        $shop = new \Shop(1);

        /** @var \ShopUrl $shopUrl */
        $shopUrl = (\ShopUrl::getShopUrls(1))->getFirst();

        /** @var ShopUrlDataConfiguration $sudc */
        $infos = $this->module->getCoreServiceContainer()->get('prestashop.adapter.meta.shop_url.configuration');

        /** @var Params $params */
        $params = null;

        $shopToken = new Token((string)$this->makeJwtToken(new \DateTimeImmutable('+1 hour'), [
            'sub' => $this->faker->uuid,
        ]));
        $ownerToken = new Token((string)$this->makeJwtToken(new \DateTimeImmutable('+1 hour'), [
            'sub' => $this->faker->uuid
        ]));
        $updateUserShopResponse = $this->createApiResponse([
            'message' => 'all good !',
        ], 200, true);

        $this->initResponse($params, $updateUserShopResponse);
        $this->initTokens($shopToken, $ownerToken);

        $newDomain = $this->faker->domainName;

        $shopUrl->domain = $newDomain;
        $shopUrl->update();

        $this->module->getLogger()->info(json_encode($params));

        // FIXME: test data exhaustively
        $this->assertEquals($shop->id, $params->shop->shopId);
        $this->assertEquals($shop->name, $params->shop->name);
        $this->assertEquals('http://' . $newDomain, $params->shop->domain);
        $this->assertEquals('https://' . $shop->domain_ssl, $params->shop->sslDomain);
        $this->assertEquals((string) $ownerToken, $params->ownerToken);
        $this->assertEquals($shopToken->getJwt()->claims()->get('sub'), $params->shopUid);
        $this->assertEquals($ownerToken->getJwt()->claims()->get('sub'), $params->ownerUid);
    }

    /**
     * @param Params|null $params
     * @param array $response
     *
     * @return void
     */
    private function initResponse(&$params, array $response)
    {
        $accountsClient = $this->createMock(AccountsClient::class);
        $accountsClient->expects($this->once())->method('updateUserShop')->willReturnCallback(function (
            $ownerUid, $shopUid, $ownerToken, UpdateShop $shop
        ) use (&$params, $response) {
            $params = (object)[
                'ownerUid' => $ownerUid,
                'shopUid' => $shopUid,
                'ownerToken' => (string)$ownerToken,
                'shop' => $shop,
            ];
            return $response;
        });
        $this->replaceProperty($this->updateUserShopHandler, 'accountClient', $accountsClient);
    }

    /**
     * @param string $shopToken
     * @param string $ownerToken
     *
     * @return void
     */
    private function initTokens($shopToken, $ownerToken)
    {
        $shopSession = $this->createMock(Firebase\ShopSession::class);
        $shopSession->method('getValidToken')->willReturn($shopToken);
        $this->replaceProperty($this->updateUserShopHandler, 'shopSession', $shopSession);

        $ownerSession = $this->createMock(Firebase\OwnerSession::class);
        $ownerSession->method('getValidToken')->willReturn($ownerToken);
        $this->replaceProperty($this->updateUserShopHandler, 'ownerSession', $ownerSession);
    }
}

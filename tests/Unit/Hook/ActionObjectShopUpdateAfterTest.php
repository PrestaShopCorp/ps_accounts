<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Hook;

use PrestaShop\Module\PsAccounts\Account\CommandHandler\UpdateUserShopHandler;
use PrestaShop\Module\PsAccounts\Account\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

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
     * @var Link
     */
    protected $link;

    /**
     * @test
     */
    public function itShouldAttemptToUpdateShop()
    {
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

        $newName = str_split($this->faker->slug(), 64)[0];

        $shop = new \Shop(1);
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
        $shopUrls = \ShopUrl::getShopUrls(1);

        /** @var \ShopUrl $shopUrl */
        $shopUrl = $shopUrls->getFirst();

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

        $domain = $this->faker->domainName;
        $domainSsl = $this->faker->domainName;
        $physicalUri = $this->faker->slug(1);
        $virtualUri = $this->faker->slug(1);
        $dashboardLink = $this->link->getDashboardLink();
        $trailingSlash = $this->link->getTrailingSlash($dashboardLink);
        $index = $this->link->getScript($dashboardLink);

        echo $dashboardLink . PHP_EOL;

        $shopUrl->domain = $domain;
        $shopUrl->domain_ssl = $domainSsl;
        $shopUrl->physical_uri = $physicalUri;
        $shopUrl->virtual_uri = $virtualUri;

        $shopUrl->update();

        #\Cache::clear();
        #\Cache::clean('Shop::setUrl_' . (int) $shopUrl->id);
        #$shopUrl->clearCache();
        $shop = new \Shop(1);

        $this->module->getLogger()->info(json_encode($params));

        // FIXME: test data exhaustively
        $this->assertEquals($shop->id, $params->shop->shopId);
        $this->assertEquals($shop->name, $params->shop->name);

        $this->assertEquals('/' . $physicalUri . '/', $params->shop->physicalUri);
        $this->assertEquals($virtualUri . '/', $params->shop->virtualUri);

        $this->assertEquals('http://' . $domain, $params->shop->domain);
        $this->assertEquals('https://' . $domainSsl, $params->shop->sslDomain);

        $this->assertEquals((string) $ownerToken, $params->ownerToken);
        $this->assertEquals($shopToken->getJwt()->claims()->get('sub'), $params->shopUid);
        $this->assertEquals($ownerToken->getJwt()->claims()->get('sub'), $params->ownerUid);

        $parsedBoBaseUrl = parse_url($params->shop->boBaseUrl);

        echo             $this->link->cleanSlashes(
            '/' . $physicalUri . _PS_ADMIN_DIR_ . ($index ? '/' . $index : '/') . $trailingSlash
        ) . PHP_EOL;
        echo $parsedBoBaseUrl['path'] . PHP_EOL;

        $this->assertEquals($domain, $parsedBoBaseUrl['host']);
        $this->assertEquals(
            $this->link->cleanSlashes(
                '/' . $physicalUri . _PS_ADMIN_DIR_ . ($index ? '/' . $index : '/') . $trailingSlash
            ),
            $parsedBoBaseUrl['path']
        );
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

<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetShopTokenTest extends TestCase
{
    /**
     * @inject
     *
     * @var PsAccountsService
     */
    protected $service;

    /**
     * @inject
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * @inject
     *
     * @var OAuth2Client
     */
    protected $oauthClient;

    public function set_up()
    {
        parent::set_up();

        $this->configurationRepository->updateAccessToken('');

        // Can't get access token without oauth2client
        $this->oauthClient->delete();
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnAValidToken()
    {
        $validToken = $this->makeJwtToken(new \DateTimeImmutable('+1 hour'));

        $this->shopSession->setToken((string) $validToken);

        $this->assertEquals($validToken, $this->service->getShopToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldThrowRefreshTokenExceptionOnError()
    {
        // FIXME: we assume we can't resolve external apis here
        $this->shopSession->setToken((string) $this->makeJwtToken(new \DateTimeImmutable('yesterday')));

        $this->expectException(RefreshTokenException::class);

        $this->service->getShopToken();
    }
}

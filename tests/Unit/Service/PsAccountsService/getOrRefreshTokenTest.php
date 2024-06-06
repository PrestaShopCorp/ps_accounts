<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class getOrRefreshTokenTest extends TestCase
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
     * @var Oauth2Client
     */
    protected $oauthClient;

    public function setUp()
    {
        parent::setUp();

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
        $this->shopSession->setToken((string) $this->makeJwtToken(new \DateTimeImmutable('+1 hour')));

        $this->assertNotEmpty($this->service->getOrRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnNullOnError()
    {
        // FIXME: we assume we can't resolve external apis here
        $this->shopSession->setToken((string) $this->makeJwtToken(new \DateTimeImmutable('yesterday')));

        $this->assertNull($this->service->getOrRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldReturnNullOnEmptyToken()
    {
        $this->shopSession->setToken('');

        $this->assertNull($this->service->getOrRefreshToken());
    }
}

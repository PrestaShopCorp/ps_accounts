<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\ShopSession;

use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Token\AccessToken;

class RefreshTokenTest extends TestCase
{
    use \PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\SessionHelpers;

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
    protected $oauth2Client;

    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopProvider;

    /**
     * @var \PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token
     */
    protected $validAccessToken;

    function setUp(): void
    {
        parent::setUp();

        $this->validAccessToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));
        $shopProvider = $this->createMock(ShopProvider::class);
        $shopProvider->method('getAccessToken')
            ->willReturn(new AccessToken([
                'access_token' => (string)$this->validAccessToken
            ]));
        $shopProvider->method('getOauth2Client')
            ->willReturn($this->oauth2Client);

        $commandBus = $this->createMock(CommandBus::class);

        $this->shopSession = new ShopSession(
            $this->configurationRepository,
            $shopProvider,
            $this->linkShop,
            $commandBus
        );

        $this->shopSession->cleanup();
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $this->shopSession->cleanup();
    }

    /**
     * @test
     */
    public function itShouldClearConfigurationAndThrowIfNoOauth()
    {
        $e = null;
        try {
            // OAuth2Client has been cleared
            $this->oauth2Client->delete();

            // Shop is linked
            $this->linkShop->update(new \PrestaShop\Module\PsAccounts\Account\Dto\LinkShop([
                'shopId' => 1,
                'uid' => $this->faker->uuid,
                'employeeId' => 5,
                'ownerUid' => $this->faker->uuid,
                'ownerEmail' => $this->faker->safeEmail,
            ]));

            $this->shopSession->setOauth2ClientReceiptTimeout(1);

            sleep(2);

            $this->shopSession->refreshToken();
        } catch (RefreshTokenException $e) {
            //$this->module->getLogger()->info($e->getMessage());
        }

        $this->assertInstanceOf(RefreshTokenException::class, $e);
        $this->assertEquals(1, preg_match('/Invalid OAuth2 client/', $e->getMessage()));
        $token = $this->shopSession->getToken();
        $this->assertEquals("", (string) $token->getJwt());
        $this->assertEquals("", (string) $token->getRefreshToken());
    }

    /**
     * @test
     */
    public function itShouldNotClearConfigurationAndThrowIfNoOauth()
    {
        $e = null;
        try {
            // Shop is linked
            $this->linkShop->update(new \PrestaShop\Module\PsAccounts\Account\Dto\LinkShop([
                'shopId' => 1,
                'uid' => $this->faker->uuid,
            ]));

            // OAuth2Client has been cleared
            $this->oauth2Client->delete();

            $this->shopSession->setOauth2ClientReceiptTimeout(1);

            //sleep(2);

            $this->shopSession->refreshToken();
        } catch (RefreshTokenException $e) {
            //$this->module->getLogger()->info($e->getMessage());
        }

        $this->assertNull($e);
        $token = $this->shopSession->getToken();
        $this->assertEquals((string) $this->validAccessToken, (string) $token->getJwt());
        $this->assertEquals("", (string) $token->getRefreshToken());
    }

    /**
     * @test
     */
    public function itShouldRefreshToken()
    {
        $e = null;
        try {
            // Shop is linked
            $this->linkShop->update(new \PrestaShop\Module\PsAccounts\Account\Dto\LinkShop([
                'shopId' => 1,
                'uid' => $this->faker->uuid,
            ]));

            // OAuth2Client exists
            $this->oauth2Client->update(
                $this->faker->uuid,
                $this->faker->password
            );

            $this->shopSession->setOauth2ClientReceiptTimeout(1);

            //sleep(2);

            $token = $this->shopSession->refreshToken();
        } catch (RefreshTokenException $e) {
            //$this->module->getLogger()->info($e->getMessage());
        }

        $this->assertNull($e);
        $this->assertEquals((string) $this->validAccessToken, (string) $token->getJwt());
        $this->assertEquals("", (string) $token->getRefreshToken());
        $this->assertEquals(new Token($this->validAccessToken), $token);
    }
}

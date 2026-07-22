<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\ShopSession;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\CachedShopStatus;
use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\AccessToken;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Client;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token;

class GetValidTokenTest extends TestCase
{
    use \PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\SessionHelpers;

    /**
     * @inject
     *
     * @var PsAccountsService
     */
    protected $psAccountsService;

    /**
     * @var ShopSession|MockObject
     */
    protected $shopSession;

    /**
     * @inject
     *
     * @var OAuth2Client
     */
    protected $oauth2Client;

    /**
     * @var OAuth2Service|MockObject
     */
    protected $oAuth2Service;

    /**
     * @var Token
     */
    protected $validAccessToken;

    /**
     * @var string
     */
    private $cloudShopId;

    function set_up()
    {
        parent::set_up();

        $this->cloudShopId = $this->faker->uuid;

        $this->validAccessToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'scp' => [
                'shop.verified',
            ],
            'aud' => [
                $this->module->getParameter('ps_accounts.token_audience'),
                'store/' . $this->cloudShopId,
            ],
        ]);

        $this->oAuth2Service = $this->createMock(OAuth2Service::class);
        $this->oAuth2Service->method('getAccessTokenByClientCredentials')
            ->willReturn(new AccessToken([
                'access_token' => (string)$this->validAccessToken
            ]));
        $this->oAuth2Service->method('getOAuth2Client')
            ->willReturn($this->oauth2Client);

        $this->shopSession = new ShopSession(
            $this->configurationRepository,
            $this->oAuth2Service,
            $this->module->getParameter('ps_accounts.accounts_api_url')
        );

        $this->shopSession->cleanup();
    }

    /**
     * @return void
     */
    public function tear_down()
    {
        parent::tear_down();

        $this->shopSession->cleanup();
    }

    /**
     * @test
     */
    public function itShouldReturnAValidIdentifiedShopToken()
    {
        $this->statusManager->setCloudShopId($this->cloudShopId);;

//        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
//            'isValid' => true,
//            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
//            'shopStatus' => new ShopStatus([
//                'cloudShopId' => $this->cloudShopId,
//                'isVerified' => true,
//            ])
//        ]))->toArray()));

        $validAccessToken = $this->makeJwtToken(new \DateTimeImmutable('+1 hour'), [
            'scp' => [
                //'shop.verified',
            ],
            'aud' => [
                $this->module->getParameter('ps_accounts.token_audience') . '/',
                'store/' . $this->cloudShopId,
            ],
        ]);

        $this->shopSession->setToken((string) $validAccessToken);

        $this->assertEquals((string) $validAccessToken, (string) $this->shopSession->getValidToken());
    }

    /**
     * @test
     */
    public function itShouldReturnAValidVerifiedShopToken()
    {
        //$this->statusManager->setCloudShopId($cloudShopId);

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->cloudShopId,
                'isVerified' => true,
            ])
        ]))->toArray()));

        $validAccessToken = $this->makeJwtToken(new \DateTimeImmutable('+1 hour'), [
            'scp' => [
                'shop.verified',
            ],
            'aud' => [
                $this->module->getParameter('ps_accounts.token_audience') . '/',
                'store/' . $this->cloudShopId,
            ],
        ]);

        $this->shopSession->setToken((string) $validAccessToken);

        $this->assertEquals((string) $validAccessToken, (string) $this->shopSession->getValidToken());
    }

    public function provideInvalidTokens()
    {
        $module = $this->getModuleInstance();

        return [
            'expired token' => [
                $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
                    'scp' => [
                        'shop.verified',
                    ],
                    'aud' => [
                        $module->getParameter('ps_accounts.token_audience'),
                        'store/' . $this->cloudShopId,
                    ]
                ]),
            ],
            'invalid scope' => [
                $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
                    'scp' => [
                        //'shop.verified',
                    ],
                    'aud' => [
                        $module->getParameter('ps_accounts.token_audience'),
                        'store/' . $this->cloudShopId,
                    ]
                ]),
            ],
            'invalid audience' => [
                $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
                    'scp' => [
                        'shop.verified',
                    ],
                    'aud' => [
                        //$module->getParameter('ps_accounts.token_audience'),
                        'store/' . $this->cloudShopId,
                    ]
                ]),
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider  provideInvalidTokens
     */
    public function itShouldRefreshInvalidVerifiedShopToken(Token $invalidAccessToken)
    {
        //$this->statusManager->setCloudShopId($cloudShopId);

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->cloudShopId,
                'isVerified' => true,
            ])
        ]))->toArray()));

        $this->shopSession->setToken((string) $invalidAccessToken);

        $this->assertEquals((string) $this->validAccessToken, (string) $this->shopSession->getValidToken());
    }

    /**
     * @test
     */
    public function itShouldApplyDefaultScopeAndAudienceWhenNoneProvided()
    {
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->cloudShopId,
                'isVerified' => true,
            ])
        ]))->toArray()));

        list($shopSession, $tokenAudience, $capture) = $this->makeCapturingShopSession();

        // no scope/audience provided + forced refresh => defaults must be resolved
        $shopSession->getValidToken(true);

        $this->assertEquals(['shop.verified'], $capture->scope);
        $this->assertEquals([
            'store/' . $this->cloudShopId,
            $tokenAudience,
        ], $capture->audience);

        $shopSession->cleanup();
    }

    /**
     * @test
     */
    public function itShouldForwardExplicitScopeAndAudienceUnchanged()
    {
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->cloudShopId,
                'isVerified' => true,
            ])
        ]))->toArray()));

        list($shopSession, $tokenAudience, $capture) = $this->makeCapturingShopSession();

        $scope = ['custom.scope'];
        $audience = ['store/custom-audience'];

        // explicit non-empty scope/audience must be forwarded as-is (no default override)
        $shopSession->getValidToken(true, true, $scope, $audience);

        $this->assertEquals($scope, $capture->scope);
        $this->assertEquals($audience, $capture->audience);

        $shopSession->cleanup();
    }

    /**
     * @test
     */
    public function itShouldForwardExplicitEmptyScopeAndAudience()
    {
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->cloudShopId,
                'isVerified' => true,
            ])
        ]))->toArray()));

        list($shopSession, $tokenAudience, $capture) = $this->makeCapturingShopSession();

        // explicit empty scope/audience must be forwarded as-is (force empty),
        // NOT replaced by the shop defaults even though the shop is verified
        $shopSession->getValidToken(true, true, [], []);

        $this->assertSame([], $capture->scope);
        $this->assertSame([], $capture->audience);

        $shopSession->cleanup();
    }

    /**
     * @test
     */
    public function itShouldForceEmptyScopeButDefaultAudienceWhenAudienceOmitted()
    {
        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->cloudShopId,
                'isVerified' => true,
            ])
        ]))->toArray()));

        list($shopSession, $tokenAudience, $capture) = $this->makeCapturingShopSession();

        // scope explicitly forced empty, audience omitted => audience defaults resolved
        $shopSession->getValidToken(true, true, []);

        $this->assertSame([], $capture->scope);
        $this->assertEquals([
            'store/' . $this->cloudShopId,
            $tokenAudience,
        ], $capture->audience);

        $shopSession->cleanup();
    }

    /**
     * Builds a ShopSession whose OAuth2Service captures the scope/audience
     * actually forwarded to getAccessTokenByClientCredentials().
     *
     * @return array [ShopSession, string $tokenAudience, object $capture]
     */
    private function makeCapturingShopSession()
    {
        $capture = new \stdClass();
        $capture->scope = null;
        $capture->audience = null;

        $accessToken = new AccessToken([
            'access_token' => (string) $this->validAccessToken,
        ]);

        $oAuth2Service = $this->createMock(OAuth2Service::class);
        $oAuth2Service->method('getOAuth2Client')
            ->willReturn($this->oauth2Client);
        $oAuth2Service->method('getAccessTokenByClientCredentials')
            ->willReturnCallback(function ($scope, $audience) use ($capture, $accessToken) {
                $capture->scope = $scope;
                $capture->audience = $audience;

                return $accessToken;
            });

        $tokenAudience = $this->module->getParameter('ps_accounts.accounts_api_url');

        $shopSession = new ShopSession(
            $this->configurationRepository,
            $oAuth2Service,
            $tokenAudience
        );
        $shopSession->cleanup();

        return [$shopSession, $tokenAudience, $capture];
    }

    /**
     * @test
     */
    public function itShouldThrowRefreshTokenExceptionOnOAuthClientError()
    {
        //$this->statusManager->setCloudShopId($cloudShopId);

        $this->oAuth2Service->method('getAccessTokenByClientCredentials')
            ->willThrowException(new OAuth2Exception());

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => (new \DateTime())->format(\DateTime::ATOM),
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $this->cloudShopId,
                'isVerified' => true,
            ])
        ]))->toArray()));

        $validAccessToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'scp' => [
                'shop.verified',
            ],
            'aud' => [
                $this->module->getParameter('ps_accounts.token_audience') . '/',
                'store/' . $this->cloudShopId,
            ],
        ]);

        $this->shopSession->setToken((string) $validAccessToken);

        $this->expectException(RefreshTokenException::class);

        $this->assertEquals((string) $validAccessToken, (string) $this->shopSession->getValidToken());
    }
}

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
        $this->shopSession->setStatusManager($this->statusManager);

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

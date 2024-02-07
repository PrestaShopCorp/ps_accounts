<?php
/**
 * 2007-2020 PrestaShop and Contributors.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\OwnerSession;

use Exception;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Session;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    private $analytics;

    function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->analytics = $this->getMockBuilder(AnalyticsService::class)
            ->setConstructorArgs(['notTheRightKey', $this->module->getService('ps_accounts.logger')])
            ->getMock();
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function itShouldRefreshTokenWhenExpired()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $ownerSession = $this->getOwnerSessionMock(['refreshToken']);
        $ownerSession->method('refreshToken')
            ->willReturn(new Token($idTokenRefreshed, $refreshToken));

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, $ownerSession->refreshToken((string) $refreshToken)->getJwt());

        $this->assertEquals((string) $refreshToken, $ownerSession->getToken()->getRefreshToken());
    }

    /**
     * @test
     *
     * @throws RefreshTokenException
     *
     * @throws Exception
     */
    public function itShouldThrowExceptionOnErrorResponse()
    {
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $client = $this->getSsoClientMock(['refreshToken']);
        $client->method('refreshToken')
            ->willReturn([
                'status' => false,
                'httpCode' => 403
            ]);

        $ownerSession = $this->getOwnerSessionMock(['getApiClient']);
        $ownerSession->method('getApiClient')
            ->willReturn($client);

        $this->expectException(RefreshTokenException::class);

        $ownerSession->refreshToken((string) $refreshToken);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function itShouldCleanupCredentialsOnFailure()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $client = $this->getSsoClientMock(['refreshToken']);
        $client->method('refreshToken')
            ->willReturn([
                'status' => false,
                'httpCode' => 403
            ]);

        $ownerSession = $this->getOwnerSessionMock(['getApiClient']);
        $ownerSession->method('getApiClient')
            ->willReturn($client);

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);
        $this->configurationRepository->updateRefreshTokenFailure('user', 0);

        $this->assertEquals(0, $this->configurationRepository->getRefreshTokenFailure('user'));
        $this->assertFalse($this->configurationRepository->getShopUnlinkedAuto());
        $this->assertEquals($idToken, (string) $ownerSession->getToken()->getJwt());
        $this->assertEquals($refreshToken, (string) $ownerSession->getToken()->getRefreshToken());

        $this->analytics->expects($this->once())
            ->method('trackMaxRefreshTokenAttempts')
            ->willReturnCallback(function ($userUid,
                                           $userEmail,
                                           $shopUid,
                                           $shopUrl) use ($idToken) {
                // FIXME make a test including both user and shop tokens
                //error_log(print_r([$userUid, $userEmail, $shopUid, $shopUrl], true));
                $this->assertEquals($idToken->claims()->get(TOKEN::ID_OWNER_CLAIM), $userUid);
                $this->assertEquals($idToken->claims()->get('email'), $userEmail);
                $this->assertNotEmpty($shopUrl);
            });

        for ($i = 0; $i < Session::MAX_REFRESH_TOKEN_ATTEMPTS; $i++) {
            try {
                $ownerSession->refreshToken((string) $refreshToken);
            } catch (RefreshTokenException $e) {
            }
        }

        $this->assertEquals(0, $this->configurationRepository->getRefreshTokenFailure('user'));
        $this->assertTrue($this->configurationRepository->getShopUnlinkedAuto());
        $this->assertEquals(null, (string) $ownerSession->getToken()->getJwt());
        $this->assertEquals(null, (string) $ownerSession->getToken()->getRefreshToken());

        /** @var LinkShop $linkShop */
        $linkShop = $this->module->getService(LinkShop::class);
        $this->assertFalse($linkShop->exists());
    }

    /**
     * @param array $methods
     *
     * @return SsoClient|\PHPUnit_Framework_MockObject_MockObject
     *
     * @throws Exception
     */
    protected function getSsoClientMock(array $methods = [])
    {
        return $this->getMockBuilder(SsoClient::class)
            ->setConstructorArgs([
                $this->module->getParameter('ps_accounts.sso_api_url')
            ])
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     * @return \PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession|\PHPUnit_Framework_MockObject_MockObject
     * @throws Exception
     */
    protected function getOwnerSessionMock(array $methods = [])
    {
        return $this->getMockBuilder(OwnerSession::class)
            ->setConstructorArgs([
                $this->module->getService(SsoClient::class),
                $this->configurationRepository,
                $this->analytics
            ])
            ->setMethods($methods)
            ->getMock();
    }
}

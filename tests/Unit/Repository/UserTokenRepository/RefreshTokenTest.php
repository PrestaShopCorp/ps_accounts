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

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\UserTokenRepository;

use Exception;
use Lcobucci\JWT\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Repository\AbstractTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    /**
     * @test
     *
     * @throws Exception
     */
    public function itShouldRefreshTokenWhenExpired()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'user_id' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $tokenRepos = $this->getUserTokenRepositoryMock(['refreshToken']);
        $tokenRepos->method('refreshToken')
            ->willReturn($idTokenRefreshed);

        $tokenRepos->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, $tokenRepos->refreshToken((string) $refreshToken));

        $this->assertEquals((string) $refreshToken, $tokenRepos->getRefreshToken());
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

        $tokenRepos = $this->getUserTokenRepositoryMock(['client']);
        $tokenRepos->method('client')
            ->willReturn($client);

        $this->expectException(RefreshTokenException::class);

        $tokenRepos->refreshToken((string) $refreshToken);
    }

    /**
     * @test
     */
    public function itShouldCleanupCredentialsOnFailure()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'user_id' => $this->faker->uuid,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $client = $this->getSsoClientMock(['refreshToken']);
        $client->method('refreshToken')
            ->willReturn([
                'status' => false,
                'httpCode' => 403
            ]);

        $tokenRepos = $this->getUserTokenRepositoryMock(['client']);
        $tokenRepos->method('client')
            ->willReturn($client);

        $tokenRepos->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->configurationRepository->updateRefreshTokenFailure('shop', 0);
        $this->assertEquals(0, $this->configurationRepository->getRefreshTokenFailure('shop'));

        for ($i = 0; $i < AbstractTokenRepository::MAX_TRIES_BEFORE_CLEAN_CREDENTIALS_ON_REFRESH_TOKEN_FAILURE; $i++) {
            try {
                $tokenRepos->refreshToken((string) $refreshToken);
            } catch (RefreshTokenException $e) {
            }
        }

        $this->assertEquals(0, $this->configurationRepository->getRefreshTokenFailure('shop'));
        $this->assertEquals(null, (string) $tokenRepos->getToken());

        /** @var ShopLinkAccountService $linkAccountService */
        $linkAccountService = $this->module->getService(ShopLinkAccountService::class);
        $this->assertFalse($linkAccountService->isAccountLinked());
    }

    /**
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AccountsClient
     *
     * @throws \Exception
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
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|UserTokenRepository
     */
    protected function getUserTokenRepositoryMock(array $methods = [])
    {
        return $this->getMockBuilder(UserTokenRepository::class)
            ->setConstructorArgs([$this->configurationRepository])
            ->setMethods($methods)
            ->getMock();
    }
}

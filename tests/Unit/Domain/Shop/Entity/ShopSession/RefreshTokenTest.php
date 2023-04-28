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

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Domain\Shop\Entity\ShopSession;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\AbstractSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Account;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Token;
use PrestaShop\Module\PsAccounts\Domain\Shop\Exception\RefreshTokenException;
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
        ]);

        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $shopSession = $this->getShopSessionMock(['refreshToken']);
        $shopSession->method('refreshToken')
            ->willReturn(new Token($idTokenRefreshed, $refreshToken));

        $shopSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, $shopSession->refreshToken((string) $refreshToken)->getToken());

        $this->assertEquals((string) $refreshToken, $shopSession->getToken()->getRefreshToken());
    }

    /**
     * @test
     *
     * @throws RefreshTokenException
     * @throws Exception
     */
    public function itShouldThrowExceptionOnErrorResponse()
    {
        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        $client = $this->getAccountsClientMock(['refreshToken']);
        $client->method('refreshToken')
            ->willReturn([
                'status' => false,
                'httpCode' => 403
            ]);

        $sessionMock = $this->getShopSessionMock(['getApiClient']);
        $sessionMock->method('getApiClient')
            ->willReturn($client);

        $this->expectException(RefreshTokenException::class);

        $sessionMock->refreshToken((string) $refreshToken);
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

        $client = $this->getAccountsClientMock(['refreshToken']);
        $client->method('refreshToken')
            ->willReturn([
                'status' => false,
                'httpCode' => 403
            ]);

        $sessionMock = $this->getShopSessionMock(['getApiClient']);
        $sessionMock->method('getApiClient')
            ->willReturn($client);

        $sessionMock->setToken((string) $idToken, (string) $refreshToken);
        $this->configurationRepository->updateRefreshTokenFailure('shop', 0);

        $this->assertEquals(0, $this->configurationRepository->getRefreshTokenFailure('shop'));
        $this->assertEquals($idToken, (string) $sessionMock->getToken()->getToken());
        $this->assertEquals($refreshToken, (string) $sessionMock->getToken()->getRefreshToken());

        for ($i = 0; $i < AbstractSession::MAX_TRIES_BEFORE_CLEAN_CREDENTIALS_ON_REFRESH_TOKEN_FAILURE; $i++) {
            try {
                $sessionMock->refreshToken((string) $refreshToken);
            } catch (RefreshTokenException $e) {
            }
        }

        $this->assertEquals(0, $this->configurationRepository->getRefreshTokenFailure('shop'));
        $this->assertEquals(null, (string) $sessionMock->getToken()->getToken());
        $this->assertEquals(null, (string) $sessionMock->getToken()->getRefreshToken());

        /** @var Account $shopAccount */
        $shopAccount = $this->module->getService(Account::class);
        $this->assertFalse($shopAccount->isLinked());
    }

    /**
     * @param array $methods
     * @return MockObject|(AccountsClient&MockObject)
     *
     * @throws Exception
     */
    protected function getAccountsClientMock(array $methods = [])
    {
        return $this->getMockBuilder(AccountsClient::class)
            ->setConstructorArgs([
                $this->module->getParameter('ps_accounts.accounts_api_url')
            ])
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     * @param array $constructorArgs
     *
     * @return MockObject|(ShopSession&MockObject)
     */
    protected function getShopSessionMock(array $methods = [])
    {
        return $this->getMockBuilder(ShopSession::class)
            ->setConstructorArgs([
                $this->module->getService(AccountsClient::class),
                $this->configurationRepository
            ])
            ->setMethods($methods)
            ->getMock();
    }
}

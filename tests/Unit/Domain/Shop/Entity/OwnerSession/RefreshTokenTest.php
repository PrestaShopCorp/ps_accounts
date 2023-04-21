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

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Domain\Shop\Entity\OwnerSession;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\AbstractSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Account;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Token;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
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

        $ownerSession = $this->getOwnerSessionMock(['refreshToken']);
        $ownerSession->method('refreshToken')
            ->willReturn(new Token($idTokenRefreshed, $refreshToken));

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, $ownerSession->refreshToken((string) $refreshToken)->getToken());

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

        $ownerSession = $this->getOwnerSessionMock(['getApiClient']);
        $ownerSession->method('getApiClient')
            ->willReturn($client);

        $ownerSession->setToken((string) $idToken, (string) $refreshToken);
        $this->configurationRepository->updateRefreshTokenFailure('user', 0);

        $this->assertEquals(0, $this->configurationRepository->getRefreshTokenFailure('user'));
        $this->assertEquals($idToken, (string) $ownerSession->getToken()->getToken());
        $this->assertEquals($refreshToken, (string) $ownerSession->getToken()->getRefreshToken());

        for ($i = 0; $i < AbstractSession::MAX_TRIES_BEFORE_CLEAN_CREDENTIALS_ON_REFRESH_TOKEN_FAILURE; $i++) {
            try {
                $ownerSession->refreshToken((string) $refreshToken);
            } catch (RefreshTokenException $e) {
            }
        }

        $this->assertEquals(0, $this->configurationRepository->getRefreshTokenFailure('user'));
        $this->assertEquals(null, (string) $ownerSession->getToken()->getToken());
        $this->assertEquals(null, (string) $ownerSession->getToken()->getRefreshToken());

        /** @var Account $linkAccountService */
        $linkAccountService = $this->module->getService(Account::class);
        $this->assertFalse($linkAccountService->isLinked());
    }

    /**
     * @param array $methods
     *
     * @return MockObject|(SsoClient&MockObject)
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
     *
     * @return MockObject|(OwnerSession&MockObject)
     */
    protected function getOwnerSessionMock(array $methods = [])
    {
        return $this->getMockBuilder(OwnerSession::class)
            ->setConstructorArgs([
                $this->module->getService(SsoClient::class),
                $this->configurationRepository
            ])
            ->setMethods($methods)
            ->getMock();
    }
}

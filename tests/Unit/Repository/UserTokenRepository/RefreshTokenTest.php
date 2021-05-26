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

use Lcobucci\JWT\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldHandleResponseSuccess()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'user_id' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        /** @var SsoClient $ssoClient */
        $ssoClient = $this->createMock(SsoClient::class);

        $ssoClient->method('refreshToken')
            ->willReturn([
                'httpCode' => 200,
                'status' => true,
                'body' => [
                    'idToken' => $idTokenRefreshed,
                    'refreshToken' => $refreshToken,
                ],
            ]);

        $tokenRepos = new UserTokenRepository($ssoClient, $configuration);

        $tokenRepos->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idTokenRefreshed, $tokenRepos->refreshToken((string) $refreshToken));

        $this->assertEquals((string) $refreshToken, $tokenRepos->getRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldHandleResponseError()
    {
        $this->expectException(RefreshTokenException::class);

        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'user_id' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        /** @var SsoClient $ssoClient */
        $ssoClient = $this->createMock(SsoClient::class);

        $ssoClient->method('refreshToken')
            ->willReturn([
                'httpCode' => 500,
                'status' => false,
                'body' => [
                    'message' => 'Error while refreshing token',
                ]
            ]);

        $tokenRepos = new UserTokenRepository($ssoClient, $configuration);

        $tokenRepos->updateCredentials((string) $idToken, (string) $refreshToken);

        $this->assertEquals((string) $idToken, $tokenRepos->getToken());

        $this->assertEquals((string) $refreshToken, $tokenRepos->getRefreshToken());

        $tokenRepos->refreshToken((string) $refreshToken);
    }
}

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

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\ShopTokenRepository;

use Lcobucci\JWT\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
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
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'));

        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);

        /** @var AccountsClient $accountsClient */
        $accountsClient = $this->createMock(AccountsClient::class);

        $accountsClient->method('refreshToken')
            ->willReturn([
                'httpCode' => 200,
                'status' => true,
                'body' => [
                    'token' => $idTokenRefreshed,
                    'refresh_token' => $refreshToken,
                ],
            ]);

        $service = new ShopTokenRepository(
            $accountsClient,
            $configuration
        );

        $this->assertEquals((string) $idTokenRefreshed, $service->refreshToken((string) $refreshToken));

        $this->assertEquals((string) $refreshToken, $configuration->getFirebaseRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldHandleResponseError()
    {
        $this->expectException(\Exception::class);

        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);

        /** @var AccountsClient $accountsClient */
        $accountsClient = $this->createMock(AccountsClient::class);

        $accountsClient->method('refreshToken')
            ->willReturn([
                'httpCode' => 500,
                'status' => false,
                'body' => [
                    'message' => 'Error while refreshing token',
                ]
            ]);

        $service = new ShopTokenRepository(
            $accountsClient,
            $configuration
        );

        $service->refreshToken((string) $refreshToken);

        $this->assertEquals((string) $idToken, $configuration->getFirebaseIdToken());

        $this->assertEquals((string) $refreshToken, $configuration->getFirebaseRefreshToken());
    }
}

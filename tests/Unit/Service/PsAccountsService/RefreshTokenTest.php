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

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\PsAccountsService;

use Lcobucci\JWT\Builder;
use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Api\Client\FirebaseClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_handle_response_success()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('yesterday'));

        $idTokenRefreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);

        /** @var FirebaseClient $firebaseClient */
        $firebaseClient = $this->createMock(FirebaseClient::class);

        $firebaseClient->method('exchangeRefreshTokenForIdToken')
            ->willReturn([
                'status' => true,
                'body' => [
                    'id_token' => $idTokenRefreshed,
                    'refresh_token' => $refreshToken,
                ],
            ]);

        /** @var PsAccountsService $service */
        $service = new PsAccountsService(
            ['accounts_ui_url' => '', 'sso_account_url' => ''],
            $this->module->getService(ConfigurationRepository::class),
            $firebaseClient,
            $this->module->getService('ps_accounts.module')
        );

        $this->assertTrue($service->refreshToken());

        $this->assertEquals((string) $idTokenRefreshed, $configuration->getFirebaseIdToken());

        $this->assertEquals((string) $refreshToken, $configuration->getFirebaseRefreshToken());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_should_handle_response_error()
    {
        $idToken = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'));

        $refreshToken = $this->makeJwtToken(new \DateTimeImmutable('+1 year'));

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $configuration->updateFirebaseIdAndRefreshTokens((string) $idToken, (string) $refreshToken);

        /** @var FirebaseClient $firebaseClient */
        $firebaseClient = $this->createMock(FirebaseClient::class);

        $firebaseClient->method('exchangeRefreshTokenForIdToken')
            ->willReturn([
                'status' => false,
            ]);

        /** @var PsAccountsService $service */
        $service = new PsAccountsService(
            ['accounts_ui_url' => '', 'sso_account_url' => ''],
            $this->module->getService(ConfigurationRepository::class),
            $firebaseClient,
            $this->module->getService('ps_accounts.module')
        );

        $this->assertFalse($service->refreshToken());

        $this->assertEquals((string) $idToken, $configuration->getFirebaseIdToken());

        $this->assertEquals((string) $refreshToken, $configuration->getFirebaseRefreshToken());
    }
}

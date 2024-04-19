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

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\Firebase\ShopSession;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\Module\PsAccounts\Tests\Unit\Account\Session\SessionHelpers;

class RefreshTokenTest extends TestCase
{
    use SessionHelpers;

    /**
     * @inject
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopProvider;

    /**
     * @test
     *
     * @throws \Exception
     * @throws RefreshTokenException
     */
    public function itShouldRefreshExpiredToken()
    {
        $expired = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $refreshed = $this->makeJwtToken(new \DateTimeImmutable('tomorrow'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $userRefreshToken = $this->faker->randomAscii;
        $shopRefreshToken = $this->faker->randomAscii;
        $shopSession = $this->getMockedShopSession($this->createApiResponse([
            'userToken' => (string) $refreshed,
            'userRefreshToken' => $userRefreshToken,
            'shopToken' => (string) $refreshed,
            'shopRefreshToken' => $shopRefreshToken,
        ], 200, true));

        $session = new Firebase\ShopSession($this->configurationRepository, $shopSession);

        //$shopSession->setToken((string) $expired);
        $session->setToken((string) $expired, $shopRefreshToken);

        $this->assertEquals((string) $expired, (string) $session->getToken());
        $this->assertEquals((string) $refreshed, (string) $session->refreshToken());
        $this->assertEquals($shopRefreshToken, $session->getToken()->getRefreshToken());
    }

    /**
     * @test
     *
     * @throws RefreshTokenException
     *
     * @throws \Exception
     */
    public function itShouldKeepTokenOnApiError()
    {
        $expired = $this->makeJwtToken(new \DateTimeImmutable('yesterday'), [
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ]);
        $shopSession = $this->getMockedShopSession($this->createApiResponse([
            'message' => 'Error !',
        ], 403, false));

        $session = new Firebase\ShopSession($this->configurationRepository, $shopSession);

        //$shopSession->setToken((string) $expired);
        $session->setToken((string) $expired);

        $this->assertEquals((string) $expired, (string) $session->refreshToken());
    }
}

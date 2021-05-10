<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Repository;

use Context;
use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
use PrestaShop\Module\PsAccounts\Configuration\ConfigOptionsResolver;
use PrestaShop\Module\PsAccounts\Configuration\Configurable;
use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;

/**
 * Class PsAccountsService
 */
class UserTokenRepository
{
    /**
     * @var SsoClient
     */
    private $ssoClient;

    /**
     * PsAccountsService constructor.
     *
     * @param SsoClient $ssoClient
     */
    public function __construct(
        SsoClient $ssoClient
    ) {
        $this->ssoClient = $ssoClient;
    }

    /**
     * @param $idToken
     * @param $refreshToken
     *
     * @return string verified or refreshed token on success
     *
     * @throws \Exception
     */
    public function verifyToken($idToken, $refreshToken)
    {
        $response = $this->ssoClient->verifyToken($idToken);

        if ($response && true == $response['status']) {
            return $idToken;
        }
        return $this->refreshToken($refreshToken);
    }

    /**
     * @param $refreshToken
     *
     * @return string idToken
     *
     * @throws \Exception
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->ssoClient->refreshToken($refreshToken);

        if ($response && true == $response['status']) {
            return $response['body']['idToken'];
        }
        throw new \Exception('Unable to refresh user token : ' . $response['httpCode'] . ' ' . $response['body']['message']);
    }
}

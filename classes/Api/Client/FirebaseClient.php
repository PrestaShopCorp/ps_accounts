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

namespace PrestaShop\Module\PsAccounts\Api\Client;

use GuzzleHttp\Client;
use PrestaShop\Module\PsAccounts\Configuration\ConfigOptionsResolver;
use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;

/**
 * Handle firebase signIn/signUp.
 */
class FirebaseClient extends GenericClient
{
    /**
     * Firebase api key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * FirebaseClient constructor.
     *
     * @param array $config
     *
     * @throws OptionResolutionException
     */
    public function __construct(array $config)
    {
        parent::__construct();

        $config = $this->resolveConfig($config);

        $client = new Client([
            'defaults' => [
                'timeout' => $this->timeout,
                'exceptions' => $this->catchExceptions,
                'allow_redirects' => false,
                'query' => [
                    'key' => $config['api_key'],
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ],
        ]);

        $this->setClient($client);
    }

    /**
     * @param string $customToken
     *
     * @return array response
     */
    public function signInWithCustomToken($customToken)
    {
        $this->setRoute('https://identitytoolkit.googleapis.com/v1/accounts:signInWithCustomToken');

        return $this->post([
            'json' => [
                'token' => $customToken,
                'returnSecureToken' => true,
            ],
        ]);
    }

    /**
     * @see https://firebase.google.com/docs/reference/rest/auth#section-refresh-token Firebase documentation
     *
     * @param string $refreshToken
     *
     * @return array response
     */
    public function exchangeRefreshTokenForIdToken($refreshToken)
    {
        $this->setRoute('https://securetoken.googleapis.com/v1/token');

        return $this->post([
            'json' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);
    }

    /**
     * @param array $config
     * @param array $defaults
     *
     * @return array
     *
     * @throws OptionResolutionException
     */
    public function resolveConfig(array $config, array $defaults = [])
    {
        return (new ConfigOptionsResolver([
            'api_key',
        ]))->resolve($config, $defaults);
    }
}

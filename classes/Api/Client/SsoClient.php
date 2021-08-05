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
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;

/**
 * Class ServicesAccountsClient
 */
class SsoClient extends GenericClient
{
    /**
     * @var UserTokenRepository
     */
    private $userTokenRepository;

    /**
     * ServicesAccountsClient constructor.
     *
     * @param string $apiUrl
     * @param Client|null $client
     *
     * @throws OptionResolutionException
     */
    public function __construct(
        $apiUrl,
        Client $client = null
    ) {
        parent::__construct();

        $config = $this->resolveConfig(['api_url' => $apiUrl]);

        // Client can be provided for tests
        if (null === $client) {
            $client = new Client([
                'base_url' => $config['api_url'],
                'defaults' => [
                    'timeout' => $this->timeout,
                    'exceptions' => $this->catchExceptions,
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            ]);
        }

        $this->setClient($client);
    }

    /**
     * @param string $idToken
     *
     * @return array response
     */
    public function verifyToken($idToken)
    {
        $this->setRoute('auth/token/verify');

        return $this->post([
            'json' => [
                'token' => $idToken,
            ],
        ]);
    }

    /**
     * @param string $refreshToken
     *
     * @return array response
     */
    public function refreshToken($refreshToken)
    {
        $this->setRoute('auth/token/refresh');

        return $this->post([
            'json' => [
                'token' => $refreshToken,
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
            'api_url',
        ]))->resolve($config, $defaults);
    }
}

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

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class Oauth2Client
{
    /**
     * @var ConfigurationRepository
     */
    private $cfRepos;

    public function __construct(ConfigurationRepository $configurationRepository)
    {
        $this->cfRepos = $configurationRepository;
    }

    /**
     * @throws \Exception
     *
     * @return bool
     */
    public function exists()
    {
        return (bool) $this->cfRepos->getOauth2ClientId();
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function delete()
    {
        $this->cfRepos->updateOauth2ClientId('');
        $this->cfRepos->updateOauth2ClientSecret('');
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return void
     */
    public function update($clientId, $clientSecret)
    {
        $this->cfRepos->updateOauth2ClientId($clientId);
        $this->cfRepos->updateOauth2ClientSecret($clientSecret);
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->cfRepos->getOauth2ClientId();
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->cfRepos->getOauth2ClientSecret();
    }
}

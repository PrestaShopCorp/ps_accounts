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

namespace PrestaShop\Module\PsAccounts\Service;

use Context;
use PrestaShop\Module\PsAccounts\Configuration\ConfigOptionsResolver;
use PrestaShop\Module\PsAccounts\Configuration\Configurable;
use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;

/**
 * Class PsAccountsService
 */
class SsoService implements Configurable
{
    /**
     * @var string
     */
    protected $ssoAccountUrl;

    /**
     * @var string
     */
    protected $ssoResendVerificationEmailUrl;

    /**
     * PsAccountsService constructor.
     *
     * @param array $config
     *
     * @throws OptionResolutionException
     */
    public function __construct(array $config)
    {
        $config = $this->resolveConfig($config);
        $this->ssoAccountUrl = $config['sso_account_url'];
        $this->ssoResendVerificationEmailUrl = $config['sso_resend_verification_email_url'];
    }

    /**
     * @return string
     */
    public function getSsoAccountUrl()
    {
        $url = $this->ssoAccountUrl;
        $langIsoCode = Context::getContext()->language->iso_code;

        return $url . '?lang=' . substr($langIsoCode, 0, 2);
    }

    /**
     * @return string
     */
    public function getSsoResendVerificationEmailUrl()
    {
        return $this->ssoResendVerificationEmailUrl;
    }

    /**
     * @param array $config
     * @param array $defaults
     *
     * @return array|mixed
     *
     * @throws OptionResolutionException
     */
    public function resolveConfig(array $config, array $defaults = [])
    {
        return (new ConfigOptionsResolver([
            'sso_account_url',
            'sso_resend_verification_email_url',
        ]))->resolve($config, $defaults);
    }
}

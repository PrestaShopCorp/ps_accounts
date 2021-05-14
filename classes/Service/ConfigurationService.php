<?php

namespace PrestaShop\Module\PsAccounts\Service;

use PrestaShop\Module\PsAccounts\Configuration\ConfigOptionsResolver;
use PrestaShop\Module\PsAccounts\Configuration\Configurable;
use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;

class ConfigurationService implements Configurable
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * ConfigurationService constructor.
     *
     * @param array $parameters
     *
     * @throws OptionResolutionException
     */
    public function __construct($parameters)
    {
        $this->parameters = $this->resolveConfig($parameters);
    }

    /**
     * @return string
     */
    public function getSsoAccountUrl()
    {
        $url = $this->parameters['sso_account_url'];
        $langIsoCode = Context::getContext()->language->iso_code;

        return $url . '?lang=' . substr($langIsoCode, 0, 2);
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
            'accounts_ui_url',
        ]))->resolve($config, $defaults);
    }
}

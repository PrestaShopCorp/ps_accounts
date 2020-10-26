<?php

namespace PrestaShop\Module\PsAccounts\DependencyInjection;

use Context;
use Module;
use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Adapter\LinkAdapter;
use PrestaShop\Module\PsAccounts\Api\Client\FirebaseClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Environment\Env;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class PsAccountsServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->singleton(Env::class, static function () {
            return new Env();
        });

        $this->singleton(FirebaseClient::class, static function () {
            return new FirebaseClient();
        });

        $this->singleton(Module::class, static function () {
            return Module::getInstanceByName('ps_accounts');
        });

        $this->singleton(Context::class, static function () {
            return Context::getContext();
        });

        $this->singleton(ShopContext::class, static function () {
            return new ShopContext();
        });

        $this->singleton(LinkAdapter::class, function () {
            return new LinkAdapter($this->get(Context::class)->link);
        });

        $this->singleton(Configuration::class, function () {
            $configuration = new Configuration();
            $configuration->setIdShop((int) $this->get(Context::class)->shop->id);

            return $configuration;
        });

        $this->singleton(ConfigurationRepository::class, function () {
            return new ConfigurationRepository($this->get(Configuration::class));
        });
    }
}

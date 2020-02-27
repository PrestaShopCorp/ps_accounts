<?php
/**
 * 2007-2019 PrestaShop and Contributors.
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
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Presenter\Store\Modules;

use PrestaShop\Module\PrestashopCheckout\Translations\Translations;
use PrestaShop\Module\PsAccounts\Presenter\PresenterInterface;

/**
 * Construct the psaccounts module.
 */
class PsAccountsModule implements PresenterInterface
{
    /**
     * @var \Context
     */
    private $context;
    /**
     * @var \Module
     */
    private $module;

    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * Present the Firebase module (vuex).
     *
     * @return array
     */
    public function present()
    {
        return [
            'psaccounts' => [
                'pubKey' => \Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY'),
                'boUrl' => preg_replace('/^https?:\/\/[^\/]+/', '', $this->context->link->getAdminLink('ConfigurePsAccounts')),
                'shopName' => \Configuration::get('PS_SHOP_NAME'),
                'nextStep' => preg_replace('/^https?:\/\/[^\/]+/', '', $this->context->link->getAdminLink('ConfigureHmacPsAccounts')),
                'protocolBo' => null,
                'domainNameBo' => null,
                'protocolDomainToValidate' => str_replace('://', '', \Tools::getProtocol(\Configuration::get('PS_SSL_ENABLED'))),
                'domainNameDomainToValidate' => str_replace(\Tools::getProtocol(\Configuration::get('PS_SSL_ENABLED')), '', \Tools::getShopDomainSsl(true)),
                'moduleVersion' => \Ps_accounts::VERSION,
                'psVersion' => _PS_VERSION_,
                'language' => $this->context->language,
                'translations' => (new Translations($this->module))->getTranslations(),
            ],
        ];
    }
}

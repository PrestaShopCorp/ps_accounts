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

namespace PrestaShop\Module\PsAccounts\Presenter\Store\Modules;

use PrestaShop\Module\PsAccounts\Presenter\PresenterInterface;
use PrestaShop\Module\PsAccounts\Translations\Translations;
use PrestaShop\Module\PsAccounts\Adapter\LinkAdapter;


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
                'svcUiUrl' => null,
                'boUrl' => preg_replace(
                    '/^https?:\/\/[^\/]+/',
                    '',
                    $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->module->name
                ),
                'shopName' => \Configuration::get('PS_SHOP_NAME'),
                'nextStep' => preg_replace(
                    '/^https?:\/\/[^\/]+/',
                    '',
                    $this->context->link->getAdminLink('AdminConfigureHmacPsAccounts')
                ),
                'protocolDomainToValidate' => str_replace(
                    '://',
                    '',
                    \Tools::getProtocol(\Configuration::get('PS_SSL_ENABLED'))
                ),
                'domainNameDomainToValidate' => str_replace(
                    \Tools::getProtocol(\Configuration::get('PS_SSL_ENABLED')),
                    '',
                    \Tools::getShopDomainSsl(true)
                ),
                'psVersion' => _PS_VERSION_,
                'language' => $this->context->language,
                'translations' => (new Translations($this->module))->getTranslations(),
                'adminController' => $this->context->link->getAdminLink('AdminAjaxPsAccounts'),
                'resetOnboardingUrl' => $this->context->link->getAdminLink('AdminAjaxPsAccounts'),
                'onboardingStarted' => \Configuration::get('PS_ACCOUNTS_RSA_PUBLIC_KEY')
                    && \Configuration::get('PS_ACCOUNTS_RSA_PRIVATE_KEY')
                    && \Configuration::get('PS_ACCOUNTS_RSA_SIGN_DATA'),
                'pageTitle' => $this->module->getPageTitle(),
                'isShopContext' => $this->isShopContext(),
                'shopsTree' => $this->getShopsTree(),
            ],
        ];
    }

        /**
     * @return bool
     */
    private function isShopContext()
    {
        if (\Shop::isFeatureActive() && \Shop::getContext() !== \Shop::CONTEXT_SHOP) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    private function getShopsTree()
    {
        $shopList = [];

        if (true === $this->isShopContext()) {
            return $shopList;
        }

        $linkAdapter = new LinkAdapter($this->context->link);

        foreach (\Shop::getTree() as $groupId => $groupData) {
            $shops = [];

            foreach ($groupData['shops'] as $shopId => $shopData) {
                $shops[] = [
                    'id' => $shopId,
                    'name' => $shopData['name'],
                    'url' => $linkAdapter->getAdminLink(
                        'AdminModules',
                        true,
                        [],
                        [
                            'configure' => $this->module->name,
                            'setShopContext' => 's-' . $shopId,
                        ]
                    ),
                ];
            }

            $shopList[] = [
                'id' => $groupId,
                'name' => $groupData['name'],
                'shops' => $shops,
            ];
        }

        return $shopList;
    }
}

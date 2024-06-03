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

namespace PrestaShop\Module\PsAccounts\Presenter\Store\Context;

use Context;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Presenter\PresenterInterface;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use Ps_accounts;

class ContextPresenter implements PresenterInterface
{
    /**
     * @var Ps_accounts
     */
    private $module;

    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * @var Context
     */
    private $context;

    public function __construct(Ps_accounts $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
        $this->psAccountsService = $this->module->getService(PsAccountsService::class);
    }

    /**
     * Check if the merchant is onboarded on ps accounts
     *
     * @return bool
     */
    private function psAccountsIsOnboarded()
    {
        if ($this->psAccountsService->getRefreshToken() && $this->psAccountsService->isEmailValidated()) {
            return true;
        }

        return false;
    }

    /**
     * Present the Context Vuex
     *
     * @return array
     *
     * @throws \PrestaShopException
     */
    public function present()
    {
        /** @var Link $linkAdapter */
        $linkAdapter = $this->module->getService(Link::class);

        /** @var ShopProvider $shopProvider */
        $shopProvider = $this->module->getService(ShopProvider::class);

        $currentShop = $shopProvider->getCurrentShop();

        return [
            'context' => [
                'app' => $this->getCurrentVueApp(),
                'user' => [
                    'psAccountsIsOnboarded' => $this->psAccountsIsOnboarded(),
                ],
                'version_ps' => _PS_VERSION_,
                'version_module' => $this->module->version,
                'shopId' => $this->psAccountsService->getShopUuidV4(),
                'isShop17' => version_compare(_PS_VERSION_, '1.7.3.0', '>='),
                'configurationLink' => $linkAdapter->getAdminLink('AdminModules', true, [], ['configure' => $this->module->name]),
                'controllersLinks' => [
                    'ajax' => $linkAdapter->getAdminLink('AdminAjaxPsAccounts'),
                ],
                'i18n' => [
                    'isoCode' => $this->context->language->iso_code,
                    'languageLocale' => $this->context->language->language_code,
                    'currencyIsoCode' => $this->context->currency->iso_code,
                ],
                'shop' => [
                    'domain' => $currentShop['domain'],
                    'url' => $currentShop['url'],
                ],
                'readmeUrl' => $this->getReadme(),
            ],
        ];
    }

    /**
     * Get Vue App to use in terms of context Controller Name
     *
     * @return string
     */
    private function getCurrentVueApp()
    {
        return 'settings';
    }

    /**
     * Get the documentation url depending on the current language
     *
     * @return string path of the doc
     */
    private function getReadme()
    {
        $isoCode = $this->context->language->iso_code;

        if (!file_exists(_PS_ROOT_DIR_ . _MODULE_DIR_ . $this->module->name . '/docs/readme_' . $isoCode . '.pdf')) {
            $isoCode = 'en';
        }

        return _MODULE_DIR_ . $this->module->name . '/docs/readme_' . $isoCode . '.pdf';
    }
}

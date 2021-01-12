<?php
/**
 * 2007-2020 PrestaShop and Contributors
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

namespace PrestaShop\Module\PsAccounts\Presenter\Store\Settings;

use Context;
use PrestaShop\Module\PsAccounts\Presenter\PresenterInterface;
use PrestaShop\Module\PsAccounts\Translations\SettingsTranslations;
use Ps_accounts;

class SettingsPresenter implements PresenterInterface
{
    /**
     * @var Ps_accounts
     */
    private $module;

    /**
     * @var Context
     */
    private $context;

    /**
     * @param Ps_accounts $module
     * @param Context $context
     */
    public function __construct(Ps_accounts $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * Present the Setting App Vuex
     *
     * @return array
     */
    public function present()
    {
        return [
            'settings' => [
                'faq' => $this->getFaq(),
                'translations' => (new SettingsTranslations($this->module))->getTranslations(),
            ],
        ];
    }

    /**
     * Retrieve the faq
     *
     * @return array|bool faq or false if no faq associated to the module
     */
    private function getFaq()
    {
//        $faq = new RetrieveFaq();
//        $faq->setModuleKey($this->module->module_key);
//        $faq->setPsVersion(_PS_VERSION_);
//        $faq->setIsoCode($this->context->language->iso_code);
//        $response = $faq->getFaq();
//
//        if (200 !== $response['httpCode']) {
//            return false;
//        }
//
//        // If no response in the selected language, retrieve the faq in the default language (english)
//        if (false === $response['body'] && $faq->getIsoCode() !== 'en') {
//            $faq->setIsoCode('en');
//            $response = $faq->getFaq();
//        }
//
//        return $response['body'];
        return false;
    }
}

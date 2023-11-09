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

namespace PrestaShop\Module\PsAccounts\Translations;

use Context;
use Ps_accounts;

class SettingsTranslations
{
    /**
     * @var Ps_accounts
     */
    private $module;

    /**
     * __construct
     *
     * @param Ps_accounts $module
     */
    public function __construct(Ps_accounts $module)
    {
        $this->module = $module;
    }

    /**
     * Create all translations for Settings App
     *
     * @return array translation list
     */
    public function getTranslations()
    {
        $locale = Context::getContext()->language->iso_code;
        $class = 'SettingsTranslations';

        $translations[$locale] = [
            'general' => [
                'settings' => $this->module->l('Settings', $class),
                'help' => $this->module->l('Help', $class),
            ],
            'configure' => [
                'incentivePanel' => [
                    'title' => $this->module->l('Your PrestaShop account', $class),
                    'howTo' => $this->module->l('One account to manage all your PrestaShop stores', $class),
                    'createPsAccount' => $this->module->l('Create your PrestaShop account or login to your existing account', $class),
                    'linkPsAccount' => $this->module->l('Link your store to your account', $class),
                ],
            ],
        ];

        return $translations;
    }
}

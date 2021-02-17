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

        $translations[$locale] = [
            'general' => [
                'settings' => $this->module->l('Settings', 'SettingsTranslations'),
                'help' => $this->module->l('Help', 'SettingsTranslations'),
            ],
            'configure' => [
                'incentivePanel' => [
                    'title' => $this->module->l('PrestaShop Account'),
                    'howTo' => $this->module->l('How to activate it? An easy 2-steps process :', 'SettingsTranslations'),
                    'createPsAccount' => $this->module->l('Create your PrestaShop Account', 'SettingsTranslations'),
                    'linkPsAccount' => $this->module->l('Link your PrestaShop Account', 'SettingsTranslations'),
                ],
            ],
        ];

        return $translations;
    }
}

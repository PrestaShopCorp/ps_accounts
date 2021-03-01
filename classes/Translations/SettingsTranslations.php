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
        $class = 'SettingsTranslations';

        $translations[$locale] = [
            'general' => [
                'settings' => $this->module->l('settings.settings', $class),
                'help' => $this->module->l('settings.help', $class),
            ],
            'configure' => [
                'incentivePanel' => [
                    'title' => $this->module->l('settings.title', $class),
                    'howTo' => $this->module->l('settings.sub_title', $class),
                    'createPsAccount' => $this->module->l('settings.step_1', $class),
                    'linkPsAccount' => $this->module->l('settings.step_2', $class),
                ],
            ],
        ];

        return $translations;
    }
}

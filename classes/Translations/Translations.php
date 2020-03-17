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

class Translations
{
    /**
     * @var \Module
     */
    private $module = null;

    /**
     * @param \Module $module
     */
    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * Create all translations (backoffice)
     *
     * @return array translation list
     */
    public function getTranslations()
    {
        $locale = \Context::getContext()->language->locale;

        $translations[$locale] = [
            'general' => [
                'startOnboarding' => $this->module->l('Start Onboarding', 'translations'),
                'restartOnboarding' => $this->module->l('Restart Onboarding', 'translations'),
                'multiShop' => [
                    'title' => $this->module->l('Multi-store mode actived', 'translations'),
                    'subtitle' => $this->module->l('You must configure your stores one by one for this service, but youwill be able to use the same accounts', 'translations'),
                    'chooseOne' => $this->module->l('Please select the first shop to configure from the list below :', 'translations'),
                    'group' => 'Group:',
                    'configure' => 'Configure',
                    'tips' => $this->module->l('After you done with the first shop, you can configure the other shops, by selector them one by one in shop selector, in horizontal menu.', 'translations'),
                ],
            ],
        ];

        return $translations;
    }
}

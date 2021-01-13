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

namespace PrestaShop\Module\PsAccounts\Presenter\Store;

use Context;
use PrestaShop\Module\PsAccounts\Presenter\PresenterInterface;
use PrestaShop\Module\PsAccounts\Presenter\Store\Context\ContextPresenter;
use PrestaShop\Module\PsAccounts\Presenter\Store\Settings\SettingsPresenter;
use PrestaShop\Module\PsAccounts\Translations\SettingsTranslations;

/**
 * Present the store to the vuejs app (vuex)
 */
class StorePresenter implements PresenterInterface
{
    /**
     * @var \Ps_accounts
     */
    private $module;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var array
     */
    private $store;

    /**
     * @param \Ps_accounts $module
     * @param Context $context
     * @param array|null $store
     */
    public function __construct(\Ps_accounts $module, Context $context, array $store = null)
    {
        // Allow to set a custom store for tests purpose
        if (null !== $store) {
            $this->store = $store;
        }

        $this->module = $module;
        $this->context = $context;
    }

    /**
     * Build the store required by vuex
     *
     * @return array
     *
     * @throws \PrestaShopException
     */
    public function present()
    {
        if (null !== $this->store) {
            return $this->store;
        }

        $contextPresenter = (new ContextPresenter($this->module, $this->context))->present();

        // Load a presenter depending on the application to load (dashboard | settings)
        $this->store = array_merge(
            $contextPresenter,
            [
                'settings' => [
                    'faq' => false,
                    'translations' => (new SettingsTranslations($this->module))->getTranslations(),
                ],
            ]
        );

        return $this->store;
    }
}

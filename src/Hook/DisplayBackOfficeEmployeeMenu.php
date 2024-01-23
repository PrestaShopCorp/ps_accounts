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

namespace PrestaShop\Module\PsAccounts\Hook;

use PrestaShop\PrestaShop\Core\Action\ActionsBarButton;

class DisplayBackOfficeEmployeeMenu extends BaseHook
{
    /**
     * @param array $params
     *
     * @return void
     *
     * @throws \Exception
     */
    public function execute(array $params = [])
    {
        $bar = $params['links'];

        $link = $this->ps_accounts->getParameter('ps_accounts.accounts_ui_url') . '?' . http_build_query([
                'utm_source' => \Tools::getShopDomain(),
                'utm_medium' => 'back-office',
                'utm_campaign' => $this->ps_accounts->name,
                'utm_content' => 'headeremployeedropdownlink',
            ]);

        $bar->add(
            new ActionsBarButton(
                '', ['link' => $link, 'icon' => 'open_in_new'], $this->ps_accounts->l('Manage your PrestaShop account')
            )
        );
    }
}

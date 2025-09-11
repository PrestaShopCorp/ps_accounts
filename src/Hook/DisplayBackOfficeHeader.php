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

use Exception;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

class DisplayBackOfficeHeader extends Hook
{
    /**
     * @return string
     *
     * @throws Exception
     */
    public function execute(array $params = [])
    {
        try {
            if (preg_match('/controller=AdminModules/', $_SERVER['REQUEST_URI']) &&
                preg_match('/configure=ps_accounts/', $_SERVER['REQUEST_URI']) ||
                preg_match('@modules/manage/action/configure/ps_accounts@', $_SERVER['REQUEST_URI'])
            ) {
                return '';
            }

            /** @var PsAccountsService $psAccountsService */
            $psAccountsService = $this->module->getService(PsAccountsService::class);

            $this->module->getContext()->controller->addJs(
                $this->module->getLocalPath() . 'views/js/notifications.js' .
                '?ctx=' . urlencode($psAccountsService->getAdminAjaxUrl() . '&action=getNotifications') .
                '&v=' . urlencode($this->module->version)
            );
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        return '';
    }
}

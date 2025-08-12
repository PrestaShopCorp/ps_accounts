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

use PrestaShop\Module\PsAccounts\Adapter\Link;

class DisplayAdminAfterHeader extends Hook
{
    /**
     * @return string
     */
    public function execute(array $params = [])
    {
        try {
            if ('ERROR' === $this->module->getParameter('ps_accounts.log_level')) {
                return '';
            }

            $cloudShopId = $this->module->getCloudShopId();
            $verified = $this->module->getVerifiedStatus('ps_accounts');
            $verifiedMsg = $verified ? 'verified' : 'NOT verified';

            /** @var Link $link */
            $link = $this->module->getService(Link::class);
            $moduleLink = $link->getAdminLink('AdminModules', true, [], [
                'configure' => 'ps_accounts',
            ]);
            $debugLink = $link->getAdminLink('AdminDebugPsAccounts');
            $healthCheckLink = $link->getLink()->getModuleLink('ps_accounts', 'apiV2ShopHealthCheck');

            $environment = $this->module->getParameter('ps_accounts.environment');

            $alertLevel = $this->getAlertLevel($environment);

            return <<<HTML
<div class="bootstrap">
    <div class="alert alert-{$alertLevel} alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <b>{$environment}</b> |
        <!-- img width="57" alt="PrestaShop Account" title="PrestaShop Account" src="/modules/ps_accounts/logo.png"-->
        <a href="{$moduleLink}">{$cloudShopId} ({$verifiedMsg})</a> |
        <a href="{$debugLink}">Debug</a> |
        <a target="_blank" href="{$healthCheckLink}">Health Check</a>
    </div>
</div>
HTML;
        } catch (\Throwable $e) {
            /* @phpstan-ignore-next-line */
        } catch (\Exception $e) {
        }

        return '';
    }

    /**
     * @param string $environment
     *
     * @return string
     */
    private function getAlertLevel($environment)
    {
        $alertLevel = 'info';
        switch ($environment) {
            case 'production':
                $alertLevel = 'info';
                break;
            case 'integration':
                $alertLevel = 'warning';
                break;
            case 'development':
                $alertLevel = 'danger';
                break;
        }

        return $alertLevel;
    }
}

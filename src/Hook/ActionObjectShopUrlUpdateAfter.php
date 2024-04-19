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

use Cache;
use Exception;
use PrestaShop\Module\PsAccounts\Account\Command\UpdateUserShopCommand;
use PrestaShop\Module\PsAccounts\Account\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Adapter\Link;

class ActionObjectShopUrlUpdateAfter extends Hook
{
    /**
     * @param array $params
     *
     * @return bool
     *
     * @throws Exception
     */
    public function execute(array $params = [])
    {
        if ($params['object']->main) {
            /** @var Link $link */
            $link = $this->module->getService(Link::class);

            Cache::clean('Shop::setUrl_' . (int) $params['object']->id);

            $shop = new \Shop($params['object']->id);

            $domain = $params['object']->domain;
            $sslDomain = $params['object']->domain_ssl;

            $response = $this->commandBus->handle(new UpdateUserShopCommand(new UpdateShop([
                'shopId' => (string) $params['object']->id,
                'name' => $shop->name,
                'domain' => 'http://' . $domain,
                'sslDomain' => 'https://' . $sslDomain,
                'physicalUri' => $params['object']->physical_uri,
                'virtualUri' => $params['object']->virtual_uri,
                'boBaseUrl' => $link->getAdminLinkWithCustomDomain(
                    $sslDomain,
                    $domain,
                    'AdminModules',
                    false,
                    [],
                    [
                        'configure' => $this->module->name,
                        'setShopContext' => 's-' . $params['object']->id,
                    ]
                ),
            ])));

            if (!$response) {
                $this->module->getLogger()->debug(
                    'Error trying to PATCH shop : No $response object'
                );
            } elseif (true !== $response['status']) {
                $this->module->getLogger()->debug(
                    'Error trying to PATCH shop : ' . $response['httpCode'] .
                    ' ' . print_r($response['body']['message'] ?: '', true)
                );
            }
        }

        return true;
    }
}

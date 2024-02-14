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
use PrestaShop\Module\PsAccounts\Account\Command\DeleteUserShopCommand;

class ActionObjectShopDeleteBefore extends Hook
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
        try {
            $response = $this->commandBus->handle(new DeleteUserShopCommand($params['object']->id));

            if (!$response) {
                $this->module->getLogger()->debug(
                    'Error trying to DELETE shop : No $response object'
                );
            } elseif (true !== $response['status']) {
                $this->module->getLogger()->debug(
                    'Error trying to DELETE shop : ' . $response['httpCode'] .
                    ' ' . print_r($response['body']['message'], true)
                );
            }
        } catch (Exception $e) {
            $this->module->getLogger()->debug(
                'Error curl while trying to DELETE shop : ' . print_r($e->getMessage(), true)
            );
        }

        return true;
    }
}

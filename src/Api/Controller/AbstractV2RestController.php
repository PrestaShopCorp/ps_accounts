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

namespace PrestaShop\Module\PsAccounts\Api\Controller;

use Context;
use PrestaShop\Module\PsAccounts\Log\Logger;

abstract class AbstractV2RestController extends AbstractRestController
{
    /**
     * @return void
     *
     * @throws \PrestaShopException
     */
    public function postProcess()
    {
        try {
            $this->checkAuthorization();
            // throw new UnauthorizedException();
            parent::postProcess();
        } catch (\Error $e) {
            $this->handleError($e);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * @return array
     */
    protected function extractPayload()
    {
        $defaultShopId = Context::getContext()->shop->id;

        return $this->decodeRawPayload($defaultShopId);
    }

    /**
     * @return bool
     */
    protected function checkAuthorization()
    {
        $authorizationHeader = $this->getRequestHeader('Authorization');
        if (!isset($authorizationHeader)) {
            return false;
        }

        $token = trim(str_replace('Bearer', '', $authorizationHeader));

        Logger::getInstance()->error($token);
    }
}

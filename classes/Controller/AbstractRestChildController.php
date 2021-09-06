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

namespace PrestaShop\Module\PsAccounts\Controller;

class AbstractRestChildController extends AbstractRestController
{
    /**
     * @var string
     */
    public $parentId = 'parent_id';

    /**
     * @return array
     */
    protected function decodePayload()
    {
        $payload = parent::decodePayload();

        if (!array_key_exists($this->parentId, $payload) ||
            !is_integer($payload[$this->parentId])) {
            $payload[$this->parentId] = $this->context->shop->id;
        }

        return $payload;
    }
}

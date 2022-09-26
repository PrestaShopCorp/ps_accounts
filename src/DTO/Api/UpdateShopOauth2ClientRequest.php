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

namespace PrestaShop\Module\PsAccounts\DTO\Api;

use PrestaShop\Module\PsAccounts\DTO\AbstractDto;
use PrestaShop\Module\PsAccounts\Exception\Http\BadRequestException;

class UpdateShopOauth2ClientRequest extends AbstractDto
{
    /** @var string */
    public $shop_id;
    /** @var string */
    public $client_id;
    /** @var string */
    public $client_secret;

    public function __construct($values = [])
    {
        parent::__construct($values);

        if (!isset($this->client_id) || !isset($this->client_secret)) {
            throw new BadRequestException('client_id and client_secret expected');
        }
    }
}

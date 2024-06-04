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

<<<<<<<< HEAD:src/Api/Controller/Request/UpdateShopOauth2ClientRequest.php
namespace PrestaShop\Module\PsAccounts\Api\Controller\Request;

class UpdateShopOauth2ClientRequest extends Request
========
namespace PrestaShop\Module\PsAccounts\Dto\Api;

use PrestaShop\Module\PsAccounts\Dto\AbstractRequest;

class UpdateShopOauth2ClientRequest extends AbstractRequest
>>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):src/Dto/Api/UpdateShopOauth2ClientRequest.php
{
    /** @var string */
    public $shop_id;
    /** @var string */
    public $client_id;
    /** @var string */
    public $client_secret;
    /** @var string */
    public $uid;

    /**
     * @var string[]
     */
    protected $mandatory = [
        'client_id',
        'client_secret',
        'uid',
    ];
}

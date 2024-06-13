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

<<<<<<<< HEAD:src/Http/Client/Guzzle/GuzzleClientFactory.php
namespace PrestaShop\Module\PsAccounts\Http\Client\Guzzle;

/**
 * Construct the guzzle client depending on PrestaShop version
 */
class GuzzleClientFactory
{
    /**
     * @param array $options
     *
     * @return GuzzleClient
     *
     * @throws \Exception
     */
    public function create($options)
    {
        return new Guzzle7Client($options);
    }
========
namespace PrestaShop\Module\PsAccounts\Domain\Shop\Dto;

use PrestaShop\Module\PsAccounts\Dto\AbstractDto;

class LinkShop extends AbstractDto
{
    /** @var string */
    public $shopId;
    /** @var string */
    public $shopRefreshToken;
    /** @var string */
    public $userRefreshToken;
    /** @var string */
    public $shopToken;
    /** @var string */
    public $userToken;
    /** @var string */
    public $employeeId = '';
>>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):src/Domain/Shop/Dto/LinkShop.php
}

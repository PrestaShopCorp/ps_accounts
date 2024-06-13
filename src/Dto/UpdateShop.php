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

<<<<<<<< HEAD:src/Account/Dto/UpdateShop.php
namespace PrestaShop\Module\PsAccounts\Account\Dto;
========
namespace PrestaShop\Module\PsAccounts\Dto;
>>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):src/Dto/UpdateShop.php

use PrestaShop\Module\PsAccounts\Type\Dto;

class UpdateShop extends Dto
{
    /**
     * @var string
     */
    public $shopId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $virtualUri;

    /**
     * @var string
     */
    public $physicalUri;

    /**
     * @var string
     */
    public $domain;

    /**
     * @var string
     */
    public $sslDomain;

    /**
     * @var string
     */
    public $boBaseUrl;

    /**
     * @var string[]
     */
    public $mandatory = [
        'shopId',
        'name',
        'virtualUri',
        'physicalUri',
        'domain',
        'sslDomain',
        'boBaseUrl',
    ];

    public function __construct($values = [])
    {
        parent::__construct($values);

        $this->domain = $this->enforceHttpScheme($this->domain, false);
        $this->sslDomain = $this->enforceHttpScheme($this->sslDomain);
    }

    public function enforceHttpScheme(string $url, bool $https = true): string
    {
        $scheme = 'http' . ($https ? 's' : '') . '://';

        return preg_replace(
            "/^(\w+:\/\/|)/",
            $scheme,
            $url
        );
    }
}

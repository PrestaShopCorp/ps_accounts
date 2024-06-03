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

namespace PrestaShop\Module\PsAccounts\Account\Dto;

use PrestaShop\Module\PsAccounts\Type\Dto;

class Shop extends Dto
{
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $domain;
    /** @var string */
    public $domainSsl;
    /** @var string */
    public $physicalUri;
    /** @var string */
    public $virtualUri;
    /** @var string */
    public $frontUrl;
    /** @var string */
    public $uuid;
    /** @var string */
    public $publicKey;
    /** @var string */
    public $employeeId;
    /** @var User */
    public $user;
    /** @var string */
    public $url;
    /** @var bool */
    public $isLinkedV4;
    /** @var bool */
    public $unlinkedAuto;

    public function __construct($values = [])
    {
        if (isset($values['user']) && is_array($values['user'])) {
            $values['user'] = new User($values['user']);
        } else {
            $values['user'] = new User();
        }
        parent::__construct($values);
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), [
            'user' => $this->user->jsonSerialize(),
        ]);
    }
}

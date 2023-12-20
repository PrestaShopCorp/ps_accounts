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

namespace PrestaShop\Module\PsAccounts\Exception\AccountLogin;

use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;

abstract class AccountLoginException extends \Exception
{
    /**
     * @var PrestaShopUser|null
     */
    private $user;

    /**
     * @var string
     */
    private $type;

    /**
     * @param PrestaShopUser|null $user
     * @param string $message
     * @param string $type
     */
    public function __construct($user, $message = '', $type = '')
    {
        parent::__construct($message);

        $this->user = $user;
        $this->type = $type;
    }

    /**
     * @return PrestaShopUser|null
     */
    public function getPrestaShopUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}

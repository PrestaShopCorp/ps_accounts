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


namespace PrestaShop\Module\PsAccounts\Api\Client\OAuth2;

use PrestaShop\Module\PsAccounts\Account\Token\Token;

class AccessToken
{
    /**
     * @var string
     */
    public $access_token;

    /**
     * @var string
     */
    public $refresh_token;

    /**
     * @var string
     */
    public $id_token;

    /**
     * @var string
     */
    public $scope;

    /**
     * @var string
     */
    public $token_type;

    /**
     * @var string
     */
    public $expires;

    /**
     * @var string
     */
    public $expires_in;

    /**
     * @var string
     */
    public $resource_owner_id;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @return bool
     */
    public function hasExpired()
    {
        $token = new Token($this->access_token);

        return $token->isExpired();
    }
}

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

namespace PrestaShop\Module\PsAccounts\Service\OAuth2\Resource;

// [OAuth2] stdClass Object (
//     [amr] => Array
//         (
//             [0] => google.com
//         )
//      [aud] => Array
//         (
//             [0] => 1a2709a2-c267-4818-a026-e076c90f7715
//         )
//     [auth_time] => 1736251524
//     [email] => john.doe@prestashop.com
//     [email_verified] => 1
//     [firstname] => John
//     [iat] => 1736251525
//     [iss] => https://oauth.prestashop.local
//     [lastname] => DOE
//     [name] => John DOE
//     [picture] => https://lh3.googleusercontent.com/a/AGNmyxZXCdlGm0FT0T0MkwjfIRQ_mft0ft3-purpeFoo-BARs96-c
//     [preferred_username] => 4rFN5bm2piPeYpsotUIwcyabcdeF
//     [providers] => Array
//         (
//             [0] => google.com
//             [1] => password
//         )
//     [rat] => 1736251522
//     [sub] => 4rFN5bm2piPeYpsotUIwcyabcdeF
// )  [] []

use PrestaShop\Module\PsAccounts\Http\Resource\Resource;

class UserInfo extends Resource
{
    /**
     * @var string
     */
    public $sub;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $firstname;

    /**
     * @var string
     */
    public $lastname;

    /**
     * @var string
     */
    public $email;

    /**
     * @var bool
     */
    public $email_verified;

    /**
     * @var string
     */
    public $picture;

    public function __construct(array $data = [])
    {
        if (isset($data['email_verified'])) {
            $data['email_verified'] = (bool) $data['email_verified'];
        }
        parent::__construct($data);
    }
}

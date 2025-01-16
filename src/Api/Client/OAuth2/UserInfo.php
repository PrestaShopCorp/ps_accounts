<?php

namespace PrestaShop\Module\PsAccounts\Api\Client\OAuth2;

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
class UserInfo
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
}

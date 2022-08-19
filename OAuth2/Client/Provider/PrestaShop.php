<?php
namespace OAuth2\Client\Provider;

use Lcobucci\JWT\Parser;
use League\OAuth2\Client\Provider\GenericProvider;

class PrestaShop extends GenericProvider
{
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct(array_merge([
            // defaults to production URLs
            'urlAuthorize'            => 'https://iam.prestashop.com/oauth2/auth',
            'urlAccessToken'          => 'https://iam.prestashop.com/oauth2/token',
            'urlResourceOwnerDetails' => 'https://iam.prestashop.com/userinfo',
        ], $options), $collaborators);
    }

//    public static function createAddonsProvider(): self
//    {
//        return new static([
//            'clientId'                => _OAUTH2_CLIENT_ID_,     // The client ID assigned to you by the provider
//            'clientSecret'            => _OAUTH2_CLIENT_SECRET_, // The client password assigned to you by the provider
//            'redirectUri'             => 'https://' . _FRONTOFFICE_SERVER_ . '/login?oauth2',
//            'urlAuthorize'            => _OAUTH2_URL_AUTHORIZE_,
//            'urlAccessToken'          => _OAUTH2_URL_ACCESS_TOKEN_,
//            'urlResourceOwnerDetails' => _OAUTH2_URL_RESOURCE_OWNER_DETAILS_,
//        ]);
//    }

    // TODO: create provider classes
    // TODO: display errors
    // TODO: hook login display
    // TODO: handle return_to (login.ts)
    public static function createShopProvider(): self
    {
        //'http://prestashop17.docker.localhost/administration/index.php?controller=AdminOAuth2PsAccounts'
        $context = \Context::getContext();
        $redirectUri = $context->link->getAdminLink('AdminOAuth2PsAccounts', false);

        /**
         * @var $module \Ps_accounts
         */
        $module = \Module::getInstanceByName('ps_accounts');

        return new static([
            'clientId'                => $module->getParameter('ps_accounts.oauth2_client_id'),
            'clientSecret'            => $module->getParameter('ps_accounts.oauth2_client_secret'),
            'redirectUri'             => $redirectUri,
            'urlAuthorize'            => $module->getParameter('ps_accounts.oauth2_url_authorize'),
            'urlAccessToken'          => $module->getParameter('ps_accounts.oauth2_url_access_token'),
            'urlResourceOwnerDetails' => $module->getParameter('ps_accounts.oauth2_url_resource_owner_details'),
        ]);
    }

    public function getDefaultScopes(): string
    {
        return 'openid offline_access';
    }

    /**
     * Helper function to return a list of claims
     *
     * @param string $token
     * @param array $claims
     *
     * @return array
     */
    public static function listTokenClaims(string $token, array $claims = []): array
    {
        $values = [];

        $parsed = (new Parser())->parse($token);

        foreach ($claims as $claim) {
            $values[] = $parsed->claims()->get($claim);
        }
        return $values;
    }

    public static function getLoginData(string $token): LoginData
    {
        list($uid, $email, $emailVerified) = self::listTokenClaims($token, [
            'sub', 'email', 'email_verified'
        ]);
        $loginData = new LoginData();
        $loginData->uid = $uid;
        $loginData->email = $email;
        $loginData->emailVerified = $emailVerified;

        return $loginData;
    }

}

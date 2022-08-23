<?php

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use League\OAuth2\Client\Token\AccessToken;
use PrestaShopCorp\OAuth2\Client\Provider\PrestaShop;

class Oauth2ClientShopProvider extends PrestaShop
{
    /**
     * @var \Ps_accounts
     */
    private $module;

    public function __construct(\Ps_accounts $module, array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        $this->module = $module;
    }

    // TODO: create factory classes for shop and addons
    // TODO: publish PrestaShop provider
    // TODO: factorize trait
    // TODO: display errors
    // TODO: hook login display
    // TODO: handle return_to (login.ts)
    // TODO: route update secret & client_id
    public static function create(): PrestaShop
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        // FIXME store into bdd
        return new self($module, [
            'clientId' => $module->getParameter('ps_accounts.oauth2_client_id'),
            'clientSecret' => $module->getParameter('ps_accounts.oauth2_client_secret'),
            'redirectUri' => self::getRedirectUri(),
        ]);
    }

    public function getBaseAuthorizationUrl(): string
    {
        return $this->module->getParameter('ps_accounts.oauth2_url_authorize');
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->module->getParameter('ps_accounts.oauth2_url_access_token');
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->module->getParameter('ps_accounts.oauth2_url_resource_owner_details');
    }

    public static function getRedirectUri(): string
    {
        /** @var \Context $context */
        $context = \Context::getContext();

        return $context->link->getAdminLink('AdminOAuth2PsAccounts', false);
    }

    /**
     * @param string $token
     *
     * @return LoginData
     */
    public function getLoginData(string $token): LoginData
    {
        list($uid, $email, $emailVerified) = self::listTokenClaims($token, [
            'sub', 'email', 'email_verified',
        ]);
        $loginData = new LoginData();
        $loginData->uid = $uid;
        $loginData->email = $email;
        $loginData->emailVerified = $emailVerified;

        return $loginData;
    }
}

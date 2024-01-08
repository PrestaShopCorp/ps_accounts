<?php

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

/**
 * {"issuer":"https://oauth.prestashop.localhost",
 * "authorization_endpoint":"https://oauth.prestashop.localhost/oauth2/auth",
 * "token_endpoint":"https://oauth.prestashop.localhost/oauth2/token",
 * "jwks_uri":"https://oauth.prestashop.localhost/.well-known/jwks.json",
 * "subject_types_supported":["public"],
 * "response_types_supported":["code","code id_token","id_token","token id_token","token","token id_token code"],
 * "claims_supported":["sub"],
 * "grant_types_supported":["authorization_code","implicit","client_credentials","refresh_token"],
 * "response_modes_supported":["query","fragment"],
 * "userinfo_endpoint":"https://oauth.prestashop.localhost/userinfo",
 * "scopes_supported":["offline_access","offline","openid"],
 * "token_endpoint_auth_methods_supported":["client_secret_post","client_secret_basic","private_key_jwt","none"],
 * "userinfo_signing_alg_values_supported":["none","RS256"],
 * "id_token_signing_alg_values_supported":["RS256"],
 * "id_token_signed_response_alg":["RS256"],
 * "userinfo_signed_response_alg":["RS256"],
 * "request_parameter_supported":true,
 * "request_uri_parameter_supported":true,
 * "require_request_uri_registration":true,
 * "claims_parameter_supported":false,
 * "revocation_endpoint":"https://oauth.prestashop.localhost/oauth2/revoke",
 * "backchannel_logout_supported":true,
 * "backchannel_logout_session_supported":true,
 * "frontchannel_logout_supported":true,
 * "frontchannel_logout_session_supported":true,
 * "end_session_endpoint":"https://oauth.prestashop.localhost/oauth2/sessions/logout",
 * "request_object_signing_alg_values_supported":["none","RS256","ES256"],
 * "code_challenge_methods_supported":["plain","S256"]}
 */
class WellKnown
{
    /** @var string */
    public $issuer;

    /** @var string */
    public $authorization_endpoint;

    /** @var string */
    public $token_endpoint;

    /** @var string */
    public $jwks_uri;

    /** @var array */
    public $subject_types_supported;

    /** @var array */
    public $response_types_supported;

    /** @var array */
    public $claims_supported;

    /** @var array */
    public $grant_types_supported;

    /** @var array */
    public $response_modes_supported;

    /** @var string */
    public $userinfo_endpoint;

    /** @var array */
    public $scopes_supported;

    /** @var array */
    public $token_endpoint_auth_methods_supported;

    /** @var array */
    public $userinfo_signing_alg_values_supported;

    /** @var array */
    public $id_token_signing_alg_values_supported;

    /** @var array */
    public $id_token_signed_response_alg;

    /** @var array */
    public $userinfo_signed_response_alg;

    /** @var bool */
    public $request_parameter_supported;

    /** @var bool */
    public $request_uri_parameter_supported;

    /** @var bool */
    public $require_request_uri_registration;

    /** @var bool */
    public $claims_parameter_supported;

    /** @var string */
    public $revocation_endpoint;

    /** @var bool */
    public $backchannel_logout_supported;

    /** @var bool */
    public $backchannel_logout_session_supported;

    /** @var bool */
    public $frontchannel_logout_supported;

    /** @var bool */
    public $frontchannel_logout_session_supported;

    /** @var string */
    public $end_session_endpoint;

    /** @var array */
    public $request_object_signing_alg_values_supported;

    /** @var array */
    public $code_challenge_methods_supported;

    /**
     * @param array|object $infos
     */
    public function __construct($infos)
    {
        foreach ($infos as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @param string $serverUrl
     *
     * @return WellKnown
     *
     * @throws \Exception
     */
    public static function fetch($serverUrl, $verifyCert = true)
    {
        $wellKnownUrl = $serverUrl . '/.well-known/openid-configuration';
        return new WellKnown(json_decode(file_get_contents($wellKnownUrl, false, stream_context_create([
            'ssl' => [
                'verify_peer' => $verifyCert,
                'verify_peer_name' => $verifyCert,
            ],
        ])), JSON_OBJECT_AS_ARRAY));
    }
}
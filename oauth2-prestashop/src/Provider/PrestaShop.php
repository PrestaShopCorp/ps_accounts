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

namespace PrestaShopCorp\OAuth2\Client\Provider;

use Lcobucci\JWT\Parser;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class PrestaShop extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string
     */
    private $responseResourceOwnerId = 'id';

    /**
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://iam.prestashop.com/oauth2/auth';
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://iam.prestashop.com/oauth2/token';
    }

    /**
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://iam.prestashop.com/userinfo';
    }

    /**
     * @return string[]
     */
    public function getDefaultScopes()
    {
        return ['openid', 'offline_access'];
    }

    /**
     * @return string
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    /**
     * @param ResponseInterface $response
     * @param $data
     *
     * @return void
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() !== 200) {
            $errorDescription = '';
            $error = '';
            if (\is_array($data) && !empty($data)) {
                $errorDescription = $data['error_description'] ?? $data['message'];
                $error = $data['error'];
            }
            throw new IdentityProviderException(sprintf('%d - %s: %s', $response->getStatusCode(), $error, $errorDescription), $response->getStatusCode(), $data);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GenericResourceOwner($response, $this->responseResourceOwnerId);
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

//
//    /**
//     * @param string $token
//     *
//     * @return LoginData
//     */
//    public static function getLoginData(string $token)
//    {
//        list($uid, $email, $emailVerified) = self::listTokenClaims($token, [
//            'sub', 'email', 'email_verified'
//        ]);
//        $loginData = new LoginData();
//        $loginData->uid = $uid;
//        $loginData->email = $email;
//        $loginData->emailVerified = $emailVerified;
//
//        return $loginData;
//    }
}

<?php

namespace PrestaShop\Module\PsAccounts\OAuth2\Token\Validator;

use PrestaShop\Module\PsAccounts\OAuth2\ApiClient;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\ExpiredException;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\JWK;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\JWT;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\SignatureInvalidException;

class Validator
{
    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @param string $token
     * @param bool $refreshJwks
     *
     * @return object decoded token
     *
     * @throws Exception\SignatureInvalidException
     * @throws Exception\TokenExpiredException
     * @throws Exception\TokenInvalidException
     */
    public function verifyToken($token, $refreshJwks = false)
    {
        // verify token signature & expiration (among others)
        try {
            $token = JWT::decode($token, JWK::parseKeySet($this->apiClient->getJwks($refreshJwks)));
        } catch (ExpiredException $e) {
            throw new Exception\TokenExpiredException($e->getMessage());
        } catch (SignatureInvalidException $e) {
            throw new Exception\SignatureInvalidException($e->getMessage());
        } catch (\UnexpectedValueException $e) {
            // FIXME: check kid header by ourselves
            if ($e->getMessage() == '"kid" invalid, unable to lookup correct key') {
                if (!$refreshJwks) {
                    return $this->verifyToken($token, true);
                }
                throw new Exception\KidInvalidException($e->getMessage());
            }
            throw new Exception\TokenInvalidException($e->getMessage());
        } catch (\Throwable $e) {
            throw new Exception\TokenInvalidException($e->getMessage());
            /* @phpstan-ignore-next-line */
        } catch (\Exception $e) {
            throw new Exception\TokenInvalidException($e->getMessage());
        }

        return $token;
    }

    /**
     * @param string $token string token to be validated
     * @param array $scope expected scope(s))
     * @param array $audience expected audience(s)
     *
     * @return object decoded token
     *
     * @throws Exception\AudienceInvalidException
     * @throws Exception\ScopeInvalidException
     * @throws Exception\SignatureInvalidException
     * @throws Exception\TokenExpiredException
     * @throws Exception\TokenInvalidException
     */
    public function validateToken($token, array $scope = [], array $audience = [])
    {
        $token = $this->verifyToken($token);
        $this->validateAudience($token, $audience);
        $this->validateScope($token, $scope);

        return $token;
    }

    /**
     * @param object $token
     * @param array $scope
     *
     * @return void
     *
     * @throws Exception\ScopeInvalidException
     */
    public function validateScope($token, array $scope)
    {
        if (!property_exists($token, 'scp')) {
            return;
        }

        // check expected scopes are included
        $scp = is_array($token->scp) ? array_unique($token->scp) : [];
        if (count(array_intersect($scope, $scp)) < count($scope)) {
            throw new Exception\ScopeInvalidException('Expected scope not matched: ' . implode(', ', $scope));
        }
    }

    /**
     * @param object $token
     * @param array $audience
     *
     * @return void
     *
     * @throws Exception\AudienceInvalidException
     */
    public function validateAudience($token, array $audience)
    {
        if (!property_exists($token, 'aud')) {
            return;
        }

        // check expected audiences are included
        $aud = is_array($token->aud) ? array_unique($token->aud) : [];
        if (count(array_intersect($audience, $aud)) < count($audience)) {
            throw new Exception\AudienceInvalidException('Expected audience not matched: ' . implode(', ', $audience));
        }
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function hasExpired($token)
    {
        try {
            $this->verifyToken($token);
        } catch (Exception\TokenExpiredException $e) {
            return true;
        } catch (Exception\TokenInvalidException $e) {
        }

        return false;
    }
}

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

namespace PrestaShop\Module\PsAccounts\Service\OAuth2\Token\Validator;

use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\ExpiredException;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\JWK;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\JWT;
use PrestaShop\Module\PsAccounts\Vendor\Firebase\JWT\SignatureInvalidException;

class Validator
{
    /**
     * @var OAuth2Service
     */
    private $oAuth2Service;

    /**
     * @var ConfigurationRepository
     */
    private $repository;

    /**
     * @var int
     */
    private $defaultLeeway;

    /**
     * @param OAuth2Service $oAuth2Service
     * @param int $defaultLeeway
     */
    public function __construct(
        OAuth2Service $oAuth2Service,
        ConfigurationRepository $repository,
        $defaultLeeway = 0
    ) {
        $this->oAuth2Service = $oAuth2Service;
        $this->repository = $repository;
        $this->defaultLeeway = $defaultLeeway;
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
            JWT::$leeway = $this->getLeeway();
            $token = JWT::decode($token, JWK::parseKeySet($this->oAuth2Service->getJwks($refreshJwks)));
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
        // check expected scopes are included
        $scp = property_exists($token, 'scp') && is_array($token->scp) ?
            array_unique($token->scp) : [];

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
        // check expected audiences are included
        $aud = property_exists($token, 'aud') && is_array($token->aud) ?
            array_unique($token->aud) : [];

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

    /**
     * @return int
     */
    public function getLeeway()
    {
        $leeway = $this->repository->getValidationLeeway();

        return is_int($leeway) ? $leeway : $this->defaultLeeway;
    }
}

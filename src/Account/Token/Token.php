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

namespace PrestaShop\Module\PsAccounts\Account\Token;

use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token\InvalidTokenStructure;

class Token
{
    const ID_OWNER_CLAIM = 'sub';

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $refreshToken;

    /**
     * @param string $token
     * @param string $refreshToken
     */
    public function __construct($token, $refreshToken = null)
    {
        $this->token = $token;
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return NullToken|\PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token
     */
    public function getJwt()
    {
        return $this->parseToken($this->token);
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        $token = $this->getJwt();

        return $token->isExpired(new \DateTime());
    }

    /**
     * @param array $scope
     *
     * @return bool
     */
    public function hasScope(array $scope)
    {
        if ($scope === []) {
            return true;
        }

        $claims = $this->getJwt()->claims();
        if (!$claims->has('scp')) {
            return false;
        }
        $scp = $claims->get('scp');

        return count(array_intersect($scope, $scp)) == count($scope);
    }

    /**
     * @param array $audience
     *
     * @return bool
     */
    public function hasAudience(array $audience)
    {
        if ($audience === []) {
            return true;
        }

        $claims = $this->getJwt()->claims();
        if (!$claims->has('aud')) {
            return false;
        }
        $aud = $claims->get('aud');

        return count(array_intersect($audience, $aud)) == count($audience);
    }

    /**
     * @param array $scope
     * @param array $audience
     *
     * @return bool
     */
    public function isValid(array $scope, array $audience)
    {
        $isValid = true;

        if ($this->isExpired()) {
            Logger::getInstance()->info(__METHOD__ . ': token isExpired ');
            $isValid = false;
        }

        if ($isValid && !$this->hasScope($scope)) {
            Logger::getInstance()->info(__METHOD__ . ': token scope invalid ');
            $isValid = false;
        }

        if ($isValid && !$this->hasAudience($audience)) {
            Logger::getInstance()->info(__METHOD__ . ': token audience invalid ');
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @return string|null
     */
    public function getUuid()
    {
        return $this->getJwt()->claims()->get(static::ID_OWNER_CLAIM);
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        // return $this->configuration->getFirebaseEmail();
        return $this->getJwt()->claims()->get('email');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->token;
    }

    /**
     * @param string $token
     *
     * @return \PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token
     */
    protected function parseToken($token)
    {
        try {
            return (new Parser())->parse((string) $token);
        } catch (InvalidTokenStructure $e) {
            return $this->getNullToken();
        }
    }

    /**
     * @return \PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token
     */
    protected function getNullToken()
    {
        //return new \Lcobucci\JWT\Token([], ['exp' => new \DateTime()]);
        return new NullToken([], ['exp' => new \DateTime()]);
    }
}

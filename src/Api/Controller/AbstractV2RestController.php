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

namespace PrestaShop\Module\PsAccounts\Api\Controller;

use Context;
use PrestaShop\Module\PsAccounts\Exception\Http\HttpException;
use PrestaShop\Module\PsAccounts\Exception\Http\UnauthorizedException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\OAuth2\Client\Provider\Exception\AudienceInvalidException;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\OAuth2\Client\Provider\Exception\ScopeInvalidException;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\OAuth2\Client\Provider\Exception\SignatureInvalidException;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\OAuth2\Client\Provider\Exception\TokenExpiredException;
use PrestaShop\Module\PsAccounts\Vendor\PrestaShop\OAuth2\Client\Provider\Exception\TokenInvalidException;

abstract class AbstractV2RestController extends AbstractRestController
{
    /**
     * @var object
     */
    protected $token;

    /**
     * @var ShopProvider
     */
    protected $oauth2Provider;

    public function __construct()
    {
        parent::__construct();

        $this->oauth2Provider = $this->module->getService(ShopProvider::class);
    }

    /**
     * @return array
     */
    protected function getScope()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getAudience()
    {
        return [];
    }

    /**
     * Controller's entry point
     *
     * @return void
     *
     * @throws \PrestaShopException
     */
    // public function init()
    // public function displayAjax()
    public function postProcess()
    {
        try {
            if ($this->authenticated) {
                $this->checkAuthorization();
            }

            $payload = $this->decodeJsonPayload();
            $httpMethod = $this->extractMethod($payload);

            $this->dispatchVerb($httpMethod, $payload);
        } catch (HttpException $e) {
            $this->module->getLogger()->error($e);

            $this->dieWithResponseJson([
                'error' => true,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            $this->handleError($e);
            /* @phpstan-ignore-next-line */
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * @return array
     *
     * @throws UnauthorizedException
     * @throws TokenInvalidException
     */
    protected function decodeJsonPayload()
    {
        $defaultShopId = Context::getContext()->shop->id;

        // FIXME: "PHP message: PHP Deprecated:  Automatically populating $HTTP_RAW_POST_DATA is deprecated and will be removed in a future version.
        // To avoid this warning set 'always_populate_raw_post_data' to '-1' in php.ini and use the php://input stream instead. in Unknown on line 0"
        $json = file_get_contents('php://input');
        $payload = !empty($json) ? json_decode($json, true) : [];

        return $this->initShopContext($payload, $defaultShopId);
    }

    /**
     * @param array $payload
     * @param int $defaultShopId
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function initShopContext(array $payload, $defaultShopId)
    {
        if (!isset($payload['shop_id'])) {
            // context fallback
            $payload['shop_id'] = $defaultShopId;
        }
        $shop = new \Shop((int) $payload['shop_id']);
        if ($shop->id) {
            $this->setContextShop($shop);
        }

        return $payload;
    }

    /**
     * @param array $payload
     *
     * @return mixed
     */
    protected function extractMethod(array & $payload)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        // detect method from payload (hack with some shop server configuration)
        if (isset($payload['method'])) {
            $method = $payload['method'];
            unset($payload['method']);
        }

        return $method;
    }

//    /**
//     * @return bool
//     */
//    protected function displayMaintenancePage()
//    {
//        return false;
//    }

    /**
     * @return void
     *
     * @throws UnauthorizedException
     */
    protected function checkAuthorization()
    {
        $authorizationHeader = $this->getRequestHeader('Authorization');
        if (!isset($authorizationHeader)) {
            throw new UnauthorizedException('Authorization header is required.');
        }

        $jwtString = trim(str_replace('Bearer', '', $authorizationHeader));

        Logger::getInstance()->info('bearer: ' . $jwtString);

        try {
            $this->token = $this->oauth2Provider->validateToken($jwtString, $this->getScope(), $this->getAudience());
        } catch (SignatureInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        } catch (AudienceInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        } catch (ScopeInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        } catch (TokenExpiredException $e) {
            throw new UnauthorizedException($e->getMessage());
        } catch (TokenInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        }
    }

    /**
     * @param array $scope
     *
     * @return void
     *
     * @throws UnauthorizedException
     */
    protected function assertScope(array $scope)
    {
        if (!$this->authenticated) {
            return;
        }

        try {
            $this->oauth2Provider->validateScope($this->token, $scope);
        } catch (ScopeInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        }
    }

    /**
     * @param array $audience
     *
     * @return void
     *
     * @throw UnauthorizedException
     */
    protected function assertAudience(array $audience)
    {
        if (!$this->authenticated) {
            return;
        }

        try {
            $this->oauth2Provider->validateAudience($this->token, $audience);
        } catch (AudienceInvalidException $e) {
            throw new UnauthorizedException($e->getMessage());
        }
    }
}

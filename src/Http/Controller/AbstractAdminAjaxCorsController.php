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

namespace PrestaShop\Module\PsAccounts\Http\Controller;

require_once __DIR__ . '/../../Polyfill/Traits/Controller/AjaxRender.php';
require_once __DIR__ . '/../../Polyfill/Traits/AdminController/IsAnonymousAllowed.php';
require_once __DIR__ . '/../../Http/Controller/GetHeader.php';

use ModuleAdminController;
use PrestaShop\Module\PsAccounts\Http\Exception\UnauthorizedException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Polyfill\Traits\AdminController\IsAnonymousAllowed;
use PrestaShop\Module\PsAccounts\Polyfill\Traits\Controller\AjaxRender;
use PrestaShop\Module\PsAccounts\Service\AdminTokenService;

abstract class AbstractAdminAjaxCorsController extends ModuleAdminController
{
    use AjaxRender;
    use IsAnonymousAllowed;
    use GetHeader;

    const HEADER_AUTHORIZATION = 'X-PrestaShop-Authorization';

    /**
     * @var \Ps_accounts
     */
    public $module;

    /**
     * @var AdminTokenService
     */
    protected $tokenService;

    /**
     * @var bool
     */
    protected $authenticated = true;

    public function __construct()
    {
        parent::__construct();

        $this->tokenService = $this->module->getService(AdminTokenService::class);

        $this->ajax = true;
        $this->content_only = true;
    }

    /**
     * @return bool
     */
    public function checkToken()
    {
        return true;
    }

    /**
     * All BO users can access the login page
     *
     * @param bool $disable
     *
     * @return bool
     */
    public function viewAccess($disable = false)
    {
        return true;
    }

    /**
     * @return void
     */
    public function init()
    {
        if (defined('_PS_VERSION_')
            && version_compare(_PS_VERSION_, '1.7.0', '>=')) {
            parent::init();
        }
    }

    /**
     * @return \ObjectModel|bool|void
     */
    public function postProcess()
    {
        try {
            if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $this->module->getParameter('ps_accounts.cors_allowed_origins'))) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                header('Access-Control-Allow-Headers: Content-Type, X-Prestashop-Authorization');
                // header('Access-Control-Allow-Private-Network: true');
                // header('Access-Control-Request-Credentials: true');
                header('Access-Control-Max-Age: 1728000');
                header('Content-Length: 0');
                header('Content-Type: text/plain');
                http_response_code(204);
                exit;
            }

            header('Content-Type: application/json');

            if ($this->authenticated) {
                $this->checkAuthorization();
            }

            return parent::postProcess();
        } catch (\Throwable $e) {
            $this->handleError($e);
            /* @phpstan-ignore-next-line */
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * @return bool
     *
     * @throws UnauthorizedException
     */
    protected function checkAuthorization()
    {
        $authorizationHeader = $this->getRequestHeader(self::HEADER_AUTHORIZATION);
        if (!isset($authorizationHeader)) {
            throw new UnauthorizedException('Authorization header is required.');
        }

        $jwtString = trim(str_replace('Bearer', '', $authorizationHeader));

        $errorMsg = 'Invalid token';

        try {
            $this->tokenService->verifyToken($jwtString);

            return true;
        } catch (\Exception $e) {
            Logger::getInstance()->error($e);
            throw new UnauthorizedException($errorMsg);
        }
    }

    /**
     * @param \Throwable|\Exception $e
     *
     * @return void
     */
    protected function handleError($e)
    {
        if ($e instanceof UnauthorizedException) {
            http_response_code(401);

            $this->ajaxRender(
                (string) json_encode([
                    'message' => $e->getMessage(),
                    'code' => 'unauthorized',
                ])
            );

            return;
        }

        http_response_code(500);

        $this->ajaxRender(
            (string) json_encode([
                'message' => $e->getMessage() ? $e->getMessage() : 'Unknown Error',
                'code' => 'unknown-error',
            ])
        );
    }
}

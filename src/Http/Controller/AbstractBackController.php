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

use Exception;
use PrestaShop\Module\PsAccounts\Http\Exception\MethodNotAllowedException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Polyfill\Traits\Controller\AjaxRender;

class AbstractBackController extends \ModuleAdminController
{
    use AjaxRender;

    public function init()
    {
        try {
            $payload = $_REQUEST;

            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $method = 'index';
                    if (isset($payload['id'])) {
                        $method = 'show';
                    }
                    break;
                case 'POST':
                    $method = 'store';
                    break;
                case 'PUT':
                case 'PATCH':
                    $method = 'update';
                    break;
                case 'DELETE':
                    $method = 'destroy';
                    break;
                case 'OPTIONS':
                    // Handle OPTIONS request
                    header('Access-Control-Allow-Origin: *'); // TODO: filter Origin with authorized domains
                    header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
                    header('Access-Control-Allow-Headers: token, Content-Type');
                    header('Access-Control-Max-Age: 1728000');
                    header('Content-Length: 0');
                    header('Content-Type: text/plain');
                    die();
                default:
                    throw new MethodNotAllowedException();
            }

            $response = call_user_func_array([$this, $method], isset($payload['id']) ? [$payload['id']] : []);

            $this->setHeaders();
            return $this->ajaxRender(json_encode($response));
        } catch (Exception $e) {
            Logger::getInstance()->error($e);

            $this->setHeaders();
            return $this->ajaxRender(json_encode(['error' => true, 'message' => 'Unknown error']));
        }
    }

    protected function setHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header('Content-Type: application/json');
    }

    // protected function getHttpMethod($payload = [])
    // {
    //     $method = $_SERVER['REQUEST_METHOD'] ? $_SERVER['REQUEST_METHOD'] : 'GET';

    //     // detect method from payload (hack with some shop server configuration)
    //     if (isset($payload['method'])) {
    //         $method = $payload['method'];
    //         unset($payload['method']);
    //     }

    //     return $method;
    // }

    // protected function invokeMethod()
    // {
    //     try {
    //         $method = new \ReflectionMethod($this, $method);
    //         $params = $method->getParameters();

    //         $args = [];

    //         if (null !== $id) {
    //             $args[] = $this->buildResource($id);
    //         }

    //         if (null !== $payload) {
    //             $args[] = $this->buildArg($payload, $params[1]);
    //         }

    //         return $method->invokeArgs($this, $args);
    //     } catch (ReflectionException $e) {
    //         throw new MethodNotAllowedException();
    //     }
    // }

    // protected function dispatch($httpMethod)
    // {
    //     switch ($httpMethod) {
    //         case 'GET':
    //             $method = null !== $id
    //                 ? RestMethod::SHOW
    //                 : RestMethod::INDEX;
    //             break;
    //         case 'POST':
    //             list($method, $statusCode) = null !== $id
    //                 ? [RestMethod::UPDATE, $statusCode]
    //                 : [RestMethod::STORE, 201];
    //             break;
    //         case 'PUT':
    //         case 'PATCH':
    //             $method = RestMethod::UPDATE;
    //             break;
    //         case 'DELETE':
    //             $statusCode = 204;
    //             $method = RestMethod::DELETE;
    //             break;
    //         default:
    //             throw new \Exception('Invalid Method : ' . $httpMethod);
    //     }
    // }
}

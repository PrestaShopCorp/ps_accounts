<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use Db;
use ModuleFrontController;
use PrestaShop\Module\PsAccounts\Api\Client\SegmentClient;
use PrestaShop\Module\PsAccounts\Formatter\JsonFormatter;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncRepository;
use PrestaShop\Module\PsAccounts\Service\ApiAuthorizationService;
use PrestaShop\Module\PsAccounts\Service\CompressionService;
use PrestaShop\Module\PsAccounts\Service\SegmentService;
use PrestaShopException;
use Tools;

class CommonApiController extends ModuleFrontController
{
    /**
     * Endpoint name
     *
     * @var string
     */
    public $type = '';
    /**
     * @var ApiAuthorizationService
     */
    protected $authorizationService;
    /**
     * @var SegmentService
     */
    protected $segmentService;

    public function __construct()
    {
        parent::__construct();

        $db = Db::getInstance();

        $this->controller_type = 'module';
        $this->segmentService = new SegmentService(
            new SegmentClient($this->context->link),
            new CompressionService(
                new JsonFormatter()
            )
        );
        $this->authorizationService = new ApiAuthorizationService(new AccountsSyncRepository($db));
    }

    /**
     * @return void
     *
     * @throws \PrestaShopDatabaseException
     */
    public function init()
    {
        $this->authorize();
    }

    /**
     * @return void
     *
     * @throws \PrestaShopDatabaseException
     */
    private function authorize()
    {
        $jobId = Tools::getValue('job_id');

        if (!$jobId || !$this->authorizationService->authorizeCall($jobId)) {
            $this->exitWithUnauthorizedStatus();
        }
    }

    /**
     * @param array|null $value
     * @param string|null $controller
     * @param string|null $method
     *
     * @throws PrestaShopException
     *
     * @return void
     */
    public function ajaxDie($value = null, $controller = null, $method = null)
    {
        parent::ajaxDie(json_encode($value), $controller, $method);
    }

    /**
     * @return void
     */
    public function exitWithErrorStatus()
    {
        header('HTTP/1.1 500 Retry later');
        exit;
    }

    /**
     * @return void
     */
    public function exitWithUnauthorizedStatus()
    {
        header('HTTP/1.1 401 Unauthorized');
        exit;
    }
}

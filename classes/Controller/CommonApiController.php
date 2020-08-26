<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use Db;
use Module;
use ModuleFrontController;
use PrestaShop\Module\PsAccounts\Api\Client\SegmentClient;
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
            new CompressionService()
        );
        $this->authorizationService = new ApiAuthorizationService(new AccountsSyncRepository($db));
    }

    public function init()
    {
        $this->authorize();
    }

    /**
     * @return void
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
     * @param null $value
     * @param null $controller
     * @param null $method
     * @throws PrestaShopException
     */
    public function ajaxDie($value = null, $controller = null, $method = null)
    {
        parent::ajaxDie(json_encode($value), $controller, $method);
    }

    public function exitWithErrorStatus()
    {
        header("HTTP/1.1 500 Retry later");
        exit;
    }

    public function exitWithUnauthorizedStatus()
    {
        header("HTTP/1.1 401 Unauthorized");
        exit;
    }
}

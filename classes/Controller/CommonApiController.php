<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use Db;
use ModuleFrontController;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncStateRepository;
use PrestaShop\Module\PsAccounts\Service\AuthorizationService;
use PrestaShop\Module\PsAccounts\Service\SegmentService;
use Tools;

class CommonApiController extends ModuleFrontController
{
    /**
     * @var AuthorizationService
     */
    protected $authorizationService;

    public $endPoint = '';
    /**
     * @var SegmentService
     */
    protected $segmentService;

    public function __construct()
    {
        parent::__construct();
        $this->controller_type = 'module';
        $this->authorizationService = new AuthorizationService(
            Db::getInstance(),
            new AccountsSyncStateRepository(
                Db::getInstance()
            )
        );
        $this->segmentService = new SegmentService();
    }

    public function init()
    {
        $this->authorize();
    }

    private function authorize()
    {
        $jobId = Tools::getValue('job_id');
        $syncId = Tools::getValue('sync_id');
        $offset = Tools::getValue('offset');

        if (!$this->authorizationService->authorizeCall($jobId, $syncId, $offset, $this->endPoint)) {
            header("HTTP/1.1 401 Unauthorized");
            exit;
        }
    }

    public function ajaxDie($value = null, $controller = null, $method = null)
    {
        parent::ajaxDie(json_encode($value), $controller, $method);
    }
}

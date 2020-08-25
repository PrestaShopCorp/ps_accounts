<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use Db;
use ModuleFrontController;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncStateRepository;
use PrestaShop\Module\PsAccounts\Service\AuthorizationService;
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
     * @var AuthorizationService
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
        $this->segmentService = new SegmentService($this->context);
        $this->authorizationService = new AuthorizationService(
            $db,
            new AccountsSyncStateRepository($db)
        );
    }

    public function init()
    {
        $this->authorize();
    }

    /**
     * @return void
     */
    private function authorize()
    {
        $jobId = Tools::getValue('job_id');
        $offset = Tools::getValue('offset');

        if (!$this->authorizationService->authorizeCall($jobId, $offset, $this->type)) {
            header("HTTP/1.1 401 Unauthorized");
            exit;
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
}

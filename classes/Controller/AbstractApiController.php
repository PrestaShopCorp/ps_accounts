<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use DateTime;
use ModuleFrontController;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncRepository;
use PrestaShop\Module\PsAccounts\Repository\PaginatedApiDataProviderInterface;
use PrestaShop\Module\PsAccounts\Service\ApiAuthorizationService;
use PrestaShop\Module\PsAccounts\Service\SegmentService;
use PrestaShopDatabaseException;
use PrestaShopException;
use Ps_accounts;
use Tools;

abstract class AbstractApiController extends ModuleFrontController
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
    /**
     * @var AccountsSyncRepository
     */
    protected $accountsSyncRepository;
    /**
     * @var Ps_accounts
     */
    public $module;

    public function __construct()
    {
        parent::__construct();

        $this->controller_type = 'module';
        $this->segmentService = $this->module->getService(SegmentService::class);
        $this->authorizationService = $this->module->getService(ApiAuthorizationService::class);
        $this->accountsSyncRepository = $this->module->getService(AccountsSyncRepository::class);
    }

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException
     */
    public function init()
    {
//        $this->authorize();
    }

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException
     */
    private function authorize()
    {
        $jobId = Tools::getValue('job_id');

        if (!$jobId || !$this->authorizationService->authorizeCall($jobId)) {
            $this->exitWithUnauthorizedStatus();
        }
    }

    /**
     * @param PaginatedApiDataProviderInterface $dataProvider
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    protected function handleDataSync(PaginatedApiDataProviderInterface $dataProvider)
    {
        if (!$jobId = Tools::getValue('job_id')) {
            $this->exitWithErrorStatus();
        }

        $langIso = Tools::getValue('lang_iso', null);
        $limit = (int) Tools::getValue('limit', 50);
        $dateNow = (new DateTime())->format(DateTime::ATOM);
        $offset = 0;
        $data = [];

        $typeSync = $this->accountsSyncRepository->findTypeSync($this->type, $langIso);

        if ($typeSync !== false && is_array($typeSync)) {
            $offset = (int) $typeSync['offset'];
        } else {
            $this->accountsSyncRepository->insertTypeSync($this->type, 0, $dateNow, $langIso);
        }

        try {
            $data = $dataProvider->getFormattedData($offset, $limit, $langIso);
        } catch (PrestaShopDatabaseException $exception) {
            $this->exitWithErrorStatus();
        }

        $response = $this->segmentService->upload($jobId, $data);

        if ($response['httpCode'] == 201) {
            $offset += $limit;
        }

        $remainingObjects = $dataProvider->getRemainingObjectsCount($offset, $langIso);

        if ($remainingObjects <= 0) {
            $remainingObjects = 0;
            $offset = 0;
        }

        $this->accountsSyncRepository->updateTypeSync($this->type, $offset, $dateNow, $langIso);

        return array_merge(
            [
                'sync_id' => $jobId,
                'total_objects' => count($data),
                'object_type' => $this->type,
                'has_remaining_objects' => $remainingObjects > 0,
                'remaining_objects' => $remainingObjects,
            ],
            $response
        );
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

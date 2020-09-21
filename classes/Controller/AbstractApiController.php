<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use DateTime;
use Db;
use ModuleFrontController;
use PrestaShop\Module\PsAccounts\Api\Client\EventBusSyncClient;
use PrestaShop\Module\PsAccounts\Api\Client\SegmentClient;
use PrestaShop\Module\PsAccounts\Formatter\JsonFormatter;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncRepository;
use PrestaShop\Module\PsAccounts\Repository\PaginatedApiDataProviderInterface;
use PrestaShop\Module\PsAccounts\Service\ApiAuthorizationService;
use PrestaShop\Module\PsAccounts\Service\CompressionService;
use PrestaShop\Module\PsAccounts\Service\SegmentService;
use PrestaShopDatabaseException;
use PrestaShopException;
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
        $this->authorizationService = new ApiAuthorizationService(
            new AccountsSyncRepository($db),
            new EventBusSyncClient($this->context->link)
        );
        $this->accountsSyncRepository = new AccountsSyncRepository(Db::getInstance());
    }

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException
     */
    public function init()
    {
        $this->authorize();
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
     * @param PaginatedApiDataProviderInterface $repository
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    protected function handleDataSync(PaginatedApiDataProviderInterface $repository)
    {
        if (!$jobId = Tools::getValue('job_id')) {
            $this->exitWithErrorStatus();
        }

        $limit = (int) Tools::getValue('limit', 50);
        $dateNow = (new DateTime())->format(DateTime::ATOM);
        $offset = 0;

        $typeSync = $this->accountsSyncRepository->findTypeSync($this->type);

        if ($typeSync !== false && is_array($typeSync)) {
            $offset = (int) $typeSync['offset'];
        } else {
            $this->accountsSyncRepository->insertTypeSync($this->type, 0, $dateNow);
        }

        $data = $repository->getFormattedData($offset, $limit);

        dump($data);
        die;

        $response = $this->segmentService->upload($jobId, $data);

        if ($response['httpCode'] == 201) {
            $offset += $limit;
        }

        $remainingObjects = $repository->getRemainingObjectsCount($offset);

        if ($remainingObjects <= 0) {
            $remainingObjects = 0;
            $offset = 0;
        }

        $this->accountsSyncRepository->updateTypeSync($this->type, $offset, $dateNow);

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

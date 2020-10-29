<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use DateTime;
use Exception;
use ModuleFrontController;
use PrestaShop\Module\PsAccounts\Exception\UnauthorizedException;
use PrestaShop\Module\PsAccounts\Provider\PaginatedApiDataProviderInterface;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncRepository;
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
     * @throws PrestaShopException
     */
    public function init()
    {
        try {
            $this->authorize();
        } catch (UnauthorizedException $exception) {
            $this->exitWithExceptionMessage($exception);
        } catch (PrestaShopDatabaseException $exception) {
            $this->exitWithExceptionMessage($exception);
        }
    }

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws UnauthorizedException
     */
    private function authorize()
    {
        $jobId = Tools::getValue('job_id');

        if (!$jobId) {
            throw new UnauthorizedException('Job ID is not defined.', 401);
        }

        $authorizationResponse = $this->authorizationService->authorizeCall($jobId);

        if (is_array($authorizationResponse)) {
            $this->exitWithResponse($authorizationResponse);
        } elseif (!$authorizationResponse) {
            throw new UnauthorizedException('Failed saving job id to database', 401);
        }
    }

    /**
     * @param PaginatedApiDataProviderInterface $dataProvider
     *
     * @return array
     */
    protected function handleDataSync(PaginatedApiDataProviderInterface $dataProvider)
    {
        $jobId = Tools::getValue('job_id');
        $langIso = Tools::getValue('lang_iso', null);
        $limit = (int) Tools::getValue('limit', 50);
        $limit = $limit == 0 ? 1000000000000 : $limit;
        $dateNow = (new DateTime())->format(DateTime::ATOM);
        $offset = 0;
        $response = [];

        try {
            $typeSync = $this->accountsSyncRepository->findTypeSync($this->type, $langIso);

            if ($typeSync !== false && is_array($typeSync)) {
                $offset = (int) $typeSync['offset'];
            } else {
                $this->accountsSyncRepository->insertTypeSync($this->type, $offset, $dateNow, $langIso);
            }

            $data = $dataProvider->getFormattedData($offset, $limit, $langIso);

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
                    'job_id' => $jobId,
                    'total_objects' => count($data),
                    'object_type' => $this->type,
                    'has_remaining_objects' => $remainingObjects > 0,
                    'remaining_objects' => $remainingObjects,
                ],
                $response
            );
        } catch (PrestaShopDatabaseException $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        return $response;
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
     * @param array $response
     *
     * @return void
     */
    protected function exitWithResponse(array $response)
    {
        $httpCode = isset($response['httpCode']) ? (int) $response['httpCode'] : 200;

        $this->dieWithResponse($response, $httpCode);
    }

    /**
     * @param Exception $exception
     *
     * @return void
     */
    protected function exitWithExceptionMessage(Exception $exception)
    {
        $response = [
            'object_type' => $this->type,
            'status' => false,
            'httpCode' => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];

        $this->dieWithResponse($response, (int) $exception->getCode());
    }

    /**
     * @param array $response
     * @param int $code
     *
     * @return void
     */
    private function dieWithResponse(array $response, $code)
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header("HTTP/1.1 $code");

        echo json_encode($response);
        die;
    }
}

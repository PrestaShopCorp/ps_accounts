<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use DateTime;
use Exception;
use ModuleFrontController;
use PrestaShop\AccountsAuth\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Config\Config;
use PrestaShop\Module\PsAccounts\Exception\EnvVarException;
use PrestaShop\Module\PsAccounts\Exception\FirebaseException;
use PrestaShop\Module\PsAccounts\Exception\QueryParamsException;
use PrestaShop\Module\PsAccounts\Provider\PaginatedApiDataProviderInterface;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncRepository;
use PrestaShop\Module\PsAccounts\Repository\IncrementalSyncRepository;
use PrestaShop\Module\PsAccounts\Repository\LanguageRepository;
use PrestaShop\Module\PsAccounts\Service\ApiAuthorizationService;
use PrestaShop\Module\PsAccounts\Service\ProxyService;
use PrestaShop\Module\PsAccounts\Service\SynchronizationService;
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
     * @var ProxyService
     */
    protected $proxyService;
    /**
     * @var AccountsSyncRepository
     */
    protected $accountsSyncRepository;
    /**
     * @var LanguageRepository
     */
    private $languageRepository;
    /**
     * @var PsAccountsService
     */
    private $psAccountsService;
    /**
     * @var IncrementalSyncRepository
     */
    protected $incrementalSyncRepository;
    /**
     * @var SynchronizationService
     */
    private $synchronizationService;
    /**
     * @var Ps_accounts
     */
    public $module;

    public function __construct()
    {
        parent::__construct();

        $this->controller_type = 'module';
        $this->proxyService = $this->module->getService(ProxyService::class);
        $this->authorizationService = $this->module->getService(ApiAuthorizationService::class);
        $this->accountsSyncRepository = $this->module->getService(AccountsSyncRepository::class);
        $this->languageRepository = $this->module->getService(LanguageRepository::class);
        $this->psAccountsService = new PsAccountsService();
        $this->incrementalSyncRepository = $this->module->getService(IncrementalSyncRepository::class);
        $this->synchronizationService = $this->module->getService(SynchronizationService::class);
    }

    /**
     * @return void
     */
    public function init()
    {
        try {
//            $this->authorize();
        } catch (PrestaShopDatabaseException $exception) {
            $this->exitWithExceptionMessage($exception);
        } catch (EnvVarException $exception) {
            $this->exitWithExceptionMessage($exception);
        } catch (FirebaseException $exception) {
            $this->exitWithExceptionMessage($exception);
        }
    }

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException|EnvVarException|FirebaseException
     */
    private function authorize()
    {
        $jobId = Tools::getValue('job_id', 'empty_job_id');

        $authorizationResponse = $this->authorizationService->authorizeCall($jobId);

        if (is_array($authorizationResponse)) {
            $this->exitWithResponse($authorizationResponse);
        } elseif (!$authorizationResponse) {
            throw new PrestaShopDatabaseException('Failed saving job id to database');
        }

        try {
            $token = $this->psAccountsService->getOrRefreshToken();
        } catch (Exception $exception) {
            throw new FirebaseException($exception->getMessage());
        }

        if (!$token) {
            throw new FirebaseException('Invalid token');
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
        $langIso = Tools::getValue('lang_iso', $this->languageRepository->getDefaultLanguageIsoCode());
        $limit = (int) Tools::getValue('limit', 50);

        if ($limit < 0) {
            $this->exitWithExceptionMessage(new QueryParamsException('Invalid URL Parameters', Config::INVALID_URL_QUERY));
        }

        $initFullSync = (int) Tools::getValue('full', 0) == 1;

        $dateNow = (new DateTime())->format(DateTime::ATOM);
        $offset = 0;
        $incrementalSync = false;
        $response = [];

        try {
            $typeSync = $this->accountsSyncRepository->findTypeSync($this->type, $langIso);

            if ($typeSync !== false && is_array($typeSync)) {
                $offset = (int) $typeSync['offset'];

                if ((int) $typeSync['full_sync_finished'] === 1 && !$initFullSync) {
                    $incrementalSync = true;
                } elseif ($initFullSync) {
                    $offset = 0;
                    $this->accountsSyncRepository->updateTypeSync($this->type, $offset, $dateNow, false, $langIso);
                }
            } else {
                $this->accountsSyncRepository->insertTypeSync($this->type, $offset, $dateNow, $langIso);
            }

            if ($incrementalSync) {
                $response = $this->synchronizationService->handleIncrementalSync($dataProvider, $this->type, $jobId, $limit, $langIso);
            } else {
                $response = $this->synchronizationService->handleFullSync($dataProvider, $this->type, $jobId, $langIso, $offset, $limit, $dateNow);
            }

            return array_merge(
                [
                    'job_id' => $jobId,
                    'object_type' => $this->type,
                ],
                $response
            );
        } catch (PrestaShopDatabaseException $exception) {
            $this->exitWithExceptionMessage($exception);
        } catch (EnvVarException $exception) {
            $this->exitWithExceptionMessage($exception);
        } catch (FirebaseException $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        return $response;
    }

    /**
     * @param array|null $value
     * @param string|null $controller
     * @param string|null $method
     *
     * @return void
     *
     * @throws PrestaShopException
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
        $code = $exception->getCode() == 0 ? 500 : $exception->getCode();

        if ($exception instanceof PrestaShopDatabaseException) {
            $code = Config::DATABASE_QUERY_ERROR_CODE;
        } elseif ($exception instanceof EnvVarException) {
            $code = Config::ENV_MISCONFIGURED_ERROR_CODE;
        } elseif ($exception instanceof FirebaseException) {
            $code = Config::REFRESH_TOKEN_ERROR_CODE;
        } elseif ($exception instanceof QueryParamsException) {
            $code = Config::INVALID_URL_QUERY;
        }

        $response = [
            'object_type' => $this->type,
            'status' => false,
            'httpCode' => $code,
            'message' => $exception->getMessage(),
        ];

        $this->dieWithResponse($response, (int) $code);
    }

    /**
     * @param array $response
     * @param int $code
     *
     * @return void
     */
    private function dieWithResponse(array $response, $code)
    {
        $httpStatusText = "HTTP/1.1 $code";

        if (array_key_exists((int) $code, Config::HTTP_STATUS_MESSAGES)) {
            $httpStatusText .= ' ' . Config::HTTP_STATUS_MESSAGES[(int) $code];
        } elseif (isset($response['body']['statusText'])) {
            $httpStatusText .= ' ' . $response['body']['statusText'];
        }

        $response['httpCode'] = (int) $code;

        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/json;charset=utf-8');
        header($httpStatusText);

        echo json_encode($response, JSON_UNESCAPED_SLASHES);

        exit;
    }
}

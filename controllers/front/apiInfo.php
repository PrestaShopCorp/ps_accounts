<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Exception\EnvVarException;
use PrestaShop\Module\PsAccounts\Repository\ServerInformationRepository;

class ps_AccountsApiInfoModuleFrontController extends AbstractApiController
{
    public $type = 'shops';

    /**
     * @throws PrestaShopException
     *
     * @return void
     */
    public function postProcess()
    {
        $jobId = Tools::getValue('job_id');

        $serverInformationRepository = $this->module->getService(ServerInformationRepository::class);

        $serverInfo = $serverInformationRepository->getServerInformation(Tools::getValue('lang_iso', null));

        try {
            $response = $this->segmentService->upload($jobId, $serverInfo);
        } catch (EnvVarException $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        $this->exitWithResponse(
            array_merge(
                [
                    'remaining_objects' => 0,
                    'total_objects' => 1,
                ],
                $response
            )
        );
    }
}

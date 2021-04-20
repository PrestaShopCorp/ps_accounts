<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractApiController;
use PrestaShop\Module\PsAccounts\Exception\EnvVarException;
use PrestaShop\Module\PsAccounts\Repository\ThemeRepository;

class ps_AccountsApiThemesModuleFrontController extends AbstractApiController
{
    public $type = 'themes';

    /**
     * @return void
     */
    public function postProcess()
    {
        $response = [];

        $jobId = Tools::getValue('job_id');

        $themeRepository = $this->module->getService(ThemeRepository::class);

        $themeInfo = $themeRepository->getThemes();

        try {
            $response = $this->proxyService->upload($jobId, $themeInfo);
        } catch (EnvVarException $exception) {
            $this->exitWithExceptionMessage($exception);
        } catch (Exception $exception) {
            $this->exitWithExceptionMessage($exception);
        }

        $this->exitWithResponse(
            array_merge(
                [
                    'remaining_objects' => 0,
                    'total_objects' => count($themeInfo),
                    'md5' => md5(
                        implode(' ', array_map(function ($payloadItem) {
                            return $payloadItem['id'];
                        }, $themeInfo))
                    ),
                ],
                $response
            )
        );
    }
}

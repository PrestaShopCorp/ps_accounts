<?php

/**
 * http://prestashop-17-herve-dev.local/api/account-token?output_format=JSON
 */

// FIXME: it won't work if you append a namespace ....
//namespace PrestaShop\Module\PsAccounts\Webservice;

use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Webservice\WebserviceOutputCustomJSON;
use PrestaShop\PrestaShop\Adapter\Entity\WebserviceOutputBuilder;
use PrestaShop\PrestaShop\Adapter\Entity\WebserviceRequest;

class WebserviceSpecificManagementAccountToken implements WebserviceSpecificManagementInterface
{
    /**
     * @var WebserviceOutputBuilder
     */
    protected $objOutput;

    /**
     * @var string
     *             FIXME : unused here
     */
    protected $output;

    /**
     * @var WebserviceRequest
     */
    protected $wsObject;

    /**
     * @var PsAccountsService
     */
    protected $psAccountService;

    /**
     * @var array
     */
    protected $urlSegment;

    /**
     * WebserviceSpecificManagementAccountToken constructor.
     */
    public function __construct()
    {
        /** @var Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');

        /* @var PsAccountsService $service */
        $this->psAccountService = $module->getService(PsAccountsService::class);
    }

    public function setUrlSegment($segments)
    {
        $this->urlSegment = $segments;

        return $this;
    }

    public function getUrlSegment()
    {
        return $this->urlSegment;
    }

    /**
     * @return WebserviceRequest
     */
    public function getWsObject()
    {
        return $this->wsObject;
    }

    /**
     * @return WebserviceOutputBuilder
     */
    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    /**
     * This must be return a string with specific values as WebserviceRequest expects.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->objOutput->getObjectRender()->overrideContent($this->output);
    }

    /**
     * @param WebserviceRequestCore $obj
     *
     * @return $this
     */
    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;

        return $this;
    }

    /**
     * @param WebserviceOutputBuilderCore $obj
     *
     * @return WebserviceSpecificManagementInterface
     *
     * @throws WebserviceException
     */
    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;

        // Override default JSON renderer
        $this->objOutput->setObjectRender(new WebserviceOutputCustomJSON());

        return $this;
    }

    public function index()
    {
        // TODO : implement verb
    }

    public function store()
    {
        // TODO : implement verb
    }

    /**
     * @throws Exception
     */
    public function show()
    {
        $this->setContent([
            'token' => $this->psAccountService->getOrRefreshToken(),
            'refreshToken' => $this->psAccountService->getRefreshToken(),
        ]);
    }

    public function update()
    {
        // TODO : implement verb
    }

    public function destroy()
    {
        // TODO : implement verb
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public function manage()
    {
        // TODO : programmatically generate token
        // @see https://devdocs.prestashop.com/1.7/webservice/tutorials/creating-access/
        // @see https://devdocs.prestashop.com/1.7/webservice/tutorials/testing-access/

        // TODO : add a auth layer ?
        // TODO : create entity resources ?

//        // TODO: api/ps_accounts/...
//        // TODO: => entry-point WebserviceSpecificManagementPsAccounts... as a dispatcher ?   $this->objectSpecificManagement = new $specificObjectName();
//        switch ($this->wsObject->urlSegment[1]) {
//            case 'token':
//                $apiClass = new TokenResourceApi();
//                break;
//        }
//        $apiClass->setObjectOutput($this->objOutput)
//            ->setWsObject($this->wsObject);
//        $apiClass->manage();

        switch ($this->wsObject->method) {
            case 'GET':
            case 'HEAD':
                //$this->show();
                if (!isset($this->wsObject->urlSegment[1]) || !strlen($this->wsObject->urlSegment[1])) {
                    // TODO : manage filtering
                    $this->index();
                } else {
                    // TODO : ...
                    $this->show();
                }
                break;
            case 'POST':
                $this->store();
                break;
            case 'PUT':
                $this->update();
                break;
            case 'DELETE':
                $this->destroy();
                break;
        }
    }

    /**
     * @param $content
     *
     * @return void
     */
    public function setContent($content)
    {
        /** @var WebserviceOutputCustomJSON $renderer */
        $renderer = $this->objOutput->getObjectRender();

        $renderer->setContent($content);
    }
}

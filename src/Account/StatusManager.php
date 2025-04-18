<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Account;

use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;

class StatusManager
{
    /**
     * Status Cache TTL in seconds
     */
    const STATUS_TTL = 10;

    /**
     * @var ConfigurationRepository
     */
    private $repository;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var AccountsService
     */
    private $accountsService;

    /**
     * @param ShopSession $shopSession
     * @param AccountsService $accountsService
     * @param ConfigurationRepository $repository
     */
    public function __construct(
        ShopSession             $shopSession,
        AccountsService         $accountsService,
        ConfigurationRepository $repository
    ) {
        $this->repository = $repository;
        $this->shopSession = $shopSession;
        $this->accountsService = $accountsService;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return !empty($this->getCloudShopId());
    }

    /**
     * @param bool $refresh
     *
     * @return ShopStatus
     *
     * @throws RefreshTokenException
     * @throws AccountsException
     * @throws UnknownStatusException
     */
    public function getStatus($refresh=true)
    {
        // TODO: avoid recursive dependencies
        // TODO: remove shop session ??
        // FIXME: command call service or service call command ?
        // CheckStatusCommand
        // VerificationFlowCommand
        if ($refresh) {

            // TODO: CircuitBreaker for that specific call with cached Response
            // TODO: implement cache ?
            ///** @var ConfigurationRepository $configuration */
            //$configuration = null;
            //if (time() - $configuration->getShopUuidDateUpd() > $command->cacheTtl) {

            $shopStatus = $this->accountsService->shopStatus(
                // TODO: cloudShopId must be set first
                $this->getCloudShopId(),
                $this->shopSession->getValidToken()
            );

            $this->repository->updateShopStatus(json_encode($shopStatus->toArray()));

            // TODO: maintain legacy configuration params
            // $this->repository->updateUserFirebaseUuid($shopStatus->pointOdContactUid);
        }

        return $this->getCachedStatus();
    }

    /**
     * @return ShopStatus
     *
     *  @throws UnknownStatusException
     */
    public function getCachedStatus()
    {
        $json = $this->repository->getShopStatus();

        return new ShopStatus(json_decode($status ? $status : '{}' , true));
    }

    /**
     * @return string
     */
    public function getCloudShopId()
    {
        return $this->repository->getShopUuid();
    }

    /**
     * @param string $cloudShopId
     */
    public function setCloudShopId($cloudShopId)
    {
        $this->repository->updateShopUuid($cloudShopId);
    }

    /**
     * @return string
     */
    public function getShopUuid()
    {
        return $this->getCloudShopId(false);
    }

    /**
     * @param bool $refresh
     *
     * @return string
     *
     * @throws AccountsException
     * @throws RefreshTokenException
     * @throws UnknownStatusException
     */
    public function getOwnerUuid($refresh=true)
    {
        // TODO
        //return $this->getStatus($refresh)->pointOdContactUid;
        return 'not-implemented';
    }

    /**
     * @param bool $refresh
     *
     * @return string
     *
     * @throws AccountsException
     * @throws RefreshTokenException
     * @throws UnknownStatusException
     */
    public function getOwnerEmail($refresh=true)
    {
        // TODO
        //return $this->getStatus($refresh)->pointOdContactEmail;
        return 'not@implemented.dev';
    }
}

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
        ShopSession $shopSession,
        AccountsService $accountsService,
        ConfigurationRepository $repository
    ) {
        $this->repository = $repository;
        $this->shopSession = $shopSession;
        $this->accountsService = $accountsService;
    }

    /**
     * @return bool
     */
    public function identityCreated()
    {
        return !empty($this->getCloudShopId());
    }

    /**
     * @param bool $refresh
     * @param int $cacheTtl
     *
     * @return ShopStatus
     *
     * @throws UnknownStatusException
     */
    public function getStatus($refresh = true, $cacheTtl = self::STATUS_TTL)
    {
        $dateUpd = $this->repository->getShopStatusDateUpd();

        if ($refresh && (!$dateUpd || time() - $dateUpd->getTimestamp() >= $cacheTtl)) {
            try {
                $this->setCachedStatus($this->accountsService->shopStatus(
                    $this->getCloudShopId(),
                    $this->shopSession->getValidToken()
                ));
            } catch (AccountsException $e) {
            } catch (RefreshTokenException $e) {
            }
        }

        return $this->getCachedStatus();
    }

    /**
     * @return ShopStatus
     *
     * @throws UnknownStatusException
     */
    public function getCachedStatus()
    {
        $status = $this->repository->getShopStatus();

        if (!$status) {
            throw new UnknownStatusException('Unknown status');
        }

        return new ShopStatus(json_decode($status, true));
    }

    /**
     * @return void
     */
    public function setCachedStatus(ShopStatus $shopStatus)
    {
        $this->repository->updateShopStatus(json_encode($shopStatus->toArray()) ?: null);

        $this->repository->updateShopUuid($shopStatus->cloudShopId);
    }

    /**
     * @return void
     */
    public function upsetCachedStatus(ShopStatus $shopStatus)
    {
        try {
            $actual = $this->getCachedStatus();
            $this->setCachedStatus(new ShopStatus(array_merge(
                $actual->toArray(false),
                $shopStatus->toArray(false)
            )));
        } catch (UnknownStatusException $e) {
            $this->setCachedStatus($shopStatus);
        }
    }

    /**
     * @param bool $refresh
     *
     * @return string|null
     */
    public function getCloudShopId($refresh = false)
    {
        try {
            return $this->getStatus($refresh)->cloudShopId;
        } catch (UnknownStatusException $e) {
            return null;
        }
    }

    /**
     * @param string $cloudShopId
     *
     * @return void
     */
    public function setCloudShopId($cloudShopId)
    {
        $this->upsetCachedStatus(new ShopStatus([
            'cloudShopId' => $cloudShopId,
        ]));
    }

    /**
     * @return string|null
     */
    public function getShopUuid()
    {
        return $this->getCloudShopId();
    }

    /**
     * @param bool $refresh
     *
     * @return string|null
     */
    public function getOwnerUuid($refresh = false)
    {
        try {
            return $this->getStatus($refresh)->pointOfContactUuid;
        } catch (UnknownStatusException $e) {
            return null;
        }
    }

    /**
     * @param bool $refresh
     *
     * @return string|null
     */
    public function getOwnerEmail($refresh = false)
    {
        try {
            return $this->getStatus($refresh)->pointOfContactEmail;
        } catch (UnknownStatusException $e) {
            return null;
        }
    }
}

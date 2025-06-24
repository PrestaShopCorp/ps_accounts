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

use DateTime;
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
    const CACHE_TTL = 30;

    /**
     * Infinite Status Cache
     */
    const CACHE_TTL_INFINITE = -1;

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
     * @param bool $cachedOnly
     * @param int $cacheTtl
     *
     * @return ShopStatus
     *
     * @throws UnknownStatusException
     */
    public function getStatus($cachedOnly = false, $cacheTtl = self::CACHE_TTL)
    {
        if (!$cachedOnly) {
            try {
                $cachedShopStatus = $this->getCachedStatus();
            } catch (UnknownStatusException $e) {
                $cachedShopStatus = null;
            }

            if (!$cachedShopStatus ||
                $this->cacheInvalidated($cachedShopStatus) ||
                $this->cacheExpired($cachedShopStatus, $cacheTtl)
            ) {
                try {
                    $this->upsetCachedStatus(new CachedShopStatus([
                        'isValid' => true,
                        'updatedAt' => date('Y-m-d H:i:s'),
                        'shopStatus' => $this->accountsService->shopStatus(
                            $this->getCloudShopId(),
                            $this->shopSession->getValidToken()
                        ),
                    ]));
                } catch (AccountsException $e) {
                } catch (RefreshTokenException $e) {
                }
            }
        }

        return $this->getCachedStatus()->shopStatus;
    }

    /**
     * @return void
     */
    public function invalidateCache()
    {
        $this->upsetCachedStatus(new CachedShopStatus([
            'isValid' => false,
        ]));
    }

    /**
     * @param CachedShopStatus $cachedStatus
     *
     * @return bool
     */
    public function cacheInvalidated(CachedShopStatus $cachedStatus = null)
    {
        try {
            $cachedStatus = $cachedStatus ?: $this->getCachedStatus();
            $isValid = $cachedStatus->isValid;
        } catch (UnknownStatusException $e) {
            $isValid = false;
        }

        return !$isValid;
    }

    /**
     * @param CachedShopStatus $cachedStatus
     * @param int $cacheTtl
     *
     * @return bool
     */
    public function cacheExpired(CachedShopStatus $cachedStatus = null, $cacheTtl = self::CACHE_TTL)
    {
        try {
            //$dateUpd = $this->getCacheDateUpd();
            $cachedStatus = $cachedStatus ?: $this->getCachedStatus();
            $dateUpd = $cachedStatus->updatedAt;

            return $dateUpd instanceof DateTime &&
                $cacheTtl != self::CACHE_TTL_INFINITE &&
                time() - $dateUpd->getTimestamp() >= $cacheTtl;
        } catch (UnknownStatusException $e) {
            return true;
        }
    }

//    /**
//     * @return \DateTime|null
//     */
//    public function getCacheDateUpd()
//    {
//        return $this->repository->getCachedShopStatusDateUpd();
//    }

    /**
     * @param bool $cachedStatus
     *
     * @return string|null
     */
    public function getCloudShopId($cachedStatus = true)
    {
        try {
            return $this->getStatus($cachedStatus)->cloudShopId;
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
        $this->upsetCachedStatus(new CachedShopStatus([
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
            ]),
        ]));
    }

    /**
     * @param bool $cachedStatus
     *
     * @return string|null
     */
    public function getPointOfContactUuid($cachedStatus = true)
    {
        try {
            return $this->getStatus($cachedStatus)->pointOfContactUuid;
        } catch (UnknownStatusException $e) {
            return null;
        }
    }

    /**
     * @param bool $cachedStatus
     *
     * @return string|null
     */
    public function getPointOfContactEmail($cachedStatus = true)
    {
        try {
            return $this->getStatus($cachedStatus)->pointOfContactEmail;
        } catch (UnknownStatusException $e) {
            return null;
        }
    }

    /**
     * @return CachedShopStatus
     *
     * @throws UnknownStatusException
     */
    protected function getCachedStatus()
    {
        $status = $this->repository->getCachedShopStatus();

        if (!$status) {
            throw new UnknownStatusException('Unknown status');
        }

        return new CachedShopStatus(json_decode($status, true));
    }

    /**
     * @return void
     */
    protected function setCachedStatus(CachedShopStatus $cachedShopStatus)
    {
        $this->repository->updateCachedShopStatus(json_encode($cachedShopStatus->toArray()) ?: null);

        $this->repository->updateShopUuid($cachedShopStatus->shopStatus->cloudShopId);
    }

    /**
     * @return void
     */
    protected function upsetCachedStatus(CachedShopStatus $cachedShopStatus)
    {
        try {
            $this->setCachedStatus(new CachedShopStatus(array_replace_recursive(
                $this->getCachedStatus()->toArray(),
                $cachedShopStatus->toArray(false)
           )));
        } catch (UnknownStatusException $e) {
            $this->setCachedStatus($cachedShopStatus);
        }
    }
}

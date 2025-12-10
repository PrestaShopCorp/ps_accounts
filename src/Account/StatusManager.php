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
     * @param bool $cachedStatus
     *
     * @return bool
     */
    public function identityVerified($cachedStatus = true)
    {
        try {
            return $this->getStatus($cachedStatus)->isVerified;
        } catch (UnknownStatusException $e) {
            return false;
        }
    }

    /**
     * @param bool $cachedOnly
     * @param int $cacheTtl
     * @param string|null $source
     *
     * @return ShopStatus
     *
     * @throws UnknownStatusException
     */
    public function getStatus($cachedOnly = false, $cacheTtl = self::CACHE_TTL, $source = null)
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
                            $this->shopSession->getValidToken(),
                            $source
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
            'shopStatus' => [
                'cloudShopId' => $cloudShopId,
            ],
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
     * @param string $pointOfContactUuid
     *
     * @return void
     */
    public function setPointOfContactUuid($pointOfContactUuid)
    {
        $this->upsetCachedStatus(new CachedShopStatus([
            'shopStatus' => [
                'pointOfContactUuid' => $pointOfContactUuid,
            ],
        ]));
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
     * @param string $pointOfContactEmail
     *
     * @return void
     */
    public function setPointOfContactEmail($pointOfContactEmail)
    {
        $this->upsetCachedStatus(new CachedShopStatus([
            'shopStatus' => [
                'pointOfContactEmail' => $pointOfContactEmail,
            ],
        ]));
    }

    /**
     * @param bool $isVerified
     *
     * @return void
     */
    public function setIsVerified($isVerified)
    {
        $this->upsetCachedStatus(new CachedShopStatus([
            'shopStatus' => [
                'isVerified' => (bool) $isVerified,
            ],
        ]));
    }

    /**
     * @param ShopStatus $status
     *
     * @return void
     */
    public function restoreStatus(ShopStatus $status)
    {
        $this->upsetCachedStatus(new CachedShopStatus([
            'isValid' => true,
            'updatedAt' => date('Y-m-d H:i:s'),
            'shopStatus' => $status,
        ]));
    }

    /**
     * @return void
     */
    public function clearStatus()
    {
        $this->upsetCachedStatus(new CachedShopStatus([
            'shopStatus' => [
                'isVerified' => false,
                'cloudShopId' => '',
                'pointOfContactUuid' => '',
                'pointOfContactEmail' => '',
                'frontendUrl' => '',
                'backOfficeUrl' => '',
                'shopVerificationErrorCode' => '',
            ],
        ]));
        $this->invalidateCache();
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
     * @param CachedShopStatus $cachedShopStatus
     *
     * @return void
     */
    protected function setCachedStatus(CachedShopStatus $cachedShopStatus)
    {
        $this->repository->updateCachedShopStatus(json_encode($cachedShopStatus->toArray()) ?: null);

        $this->repository->updateShopUuid($cachedShopStatus->shopStatus->cloudShopId);
    }

    /**
     * @param CachedShopStatus $cachedShopStatus
     * @param bool $all all fields or only explicitly initialized fields
     *
     * @return void
     */
    protected function upsetCachedStatus(CachedShopStatus $cachedShopStatus, $all = false)
    {
        try {
            $this->setCachedStatus(new CachedShopStatus(array_replace_recursive(
                $this->getCachedStatus()->toArray(),
                $cachedShopStatus->toArray($all)
           )));
        } catch (UnknownStatusException $e) {
            $this->setCachedStatus($cachedShopStatus);
        }
    }
}

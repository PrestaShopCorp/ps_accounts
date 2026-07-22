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
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Traits\WithOriginAndSourceTrait;

/**
 * @method $this withThrowException(bool $throwException)
 * @method bool getThrowException(bool $restoreDefault = true)
 * @method void resetThrowException()
 */
class StatusManager
{
    use WithOriginAndSourceTrait {
        getDefaults as WithOriginAndSourceTrait_getDefaults;
    }

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
     * @var bool
     */
    private $throwException;

    /**
     * @var callable[]
     */
    private $onBeforeStatusUpsertListeners = [];

    /**
     * Re-entrancy guard: a listener that calls getStatus() would re-trigger upsetCachedStatus.
     *
     * @var bool
     */
    private $firingOnBeforeStatusUpsert = false;

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

        $this->resetProperties();
    }

    /**
     * @return array
     */
    public function getDefaults()
    {
        return array_merge($this->WithOriginAndSourceTrait_getDefaults(), [
            'throwException' => false,
        ]);
    }

    /**
     * Register a callback invoked right before each status write.
     *
     * Signature: function (ShopStatus|null $current, ShopStatus $new): void
     *
     * The callback is called on every upsert (including cache refreshes), so it is up to the
     * consumer to compare $current and $new and decide whether a meaningful change occurred.
     * A callback that throws is logged and swallowed: it must never break status/token persistence.
     *
     * @param callable $listener
     *
     * @return $this
     */
    public function addOnBeforeStatusUpsert($listener)
    {
        $this->onBeforeStatusUpsertListeners[] = $listener;

        return $this;
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
     *
     * @return ShopStatus
     *
     * @throws AccountsException
     * @throws RefreshTokenException
     * @throws UnknownStatusException
     */
    public function getStatus($cachedOnly = false, $cacheTtl = self::CACHE_TTL)
    {
        $handleException = function ($e) {
            Logger::getInstance()->error($e->getMessage());
            if ($this->getThrowException(false)) {
                throw $e;
            }
        };

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
                        'shopStatus' => $this->accountsService
                            ->withSource($this->getSource())
                            ->shopStatus(
                                $this->getCloudShopId(),
                                $this->shopSession->getValidToken()
                            ),
                    ]));
                } catch (AccountsException $e) {
                    $handleException($e);
                } catch (RefreshTokenException $e) {
                    $handleException($e);
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
     * @param CachedShopStatus|null $cachedStatus
     *
     * @return bool
     */
    public function cacheInvalidated($cachedStatus = null)
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
     * @param CachedShopStatus|null $cachedStatus
     * @param int $cacheTtl
     *
     * @return bool
     */
    public function cacheExpired($cachedStatus = null, $cacheTtl = self::CACHE_TTL)
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
            $current = $this->getCachedStatus();
            $new = new CachedShopStatus(array_replace_recursive(
                $current->toArray(),
                $cachedShopStatus->toArray($all)
            ));
        } catch (UnknownStatusException $e) {
            $current = null; // first write / identity not created yet
            $new = $cachedShopStatus;
        }

        $this->fireOnBeforeStatusUpsert(
            ($current && $current->shopStatus instanceof ShopStatus) ? $current->shopStatus : null,
            ($new->shopStatus instanceof ShopStatus) ? $new->shopStatus : null
        );

        $this->setCachedStatus($new);
    }

    /**
     * @param ShopStatus|null $current
     * @param ShopStatus|null $new
     *
     * @return void
     */
    private function fireOnBeforeStatusUpsert($current, $new)
    {
        // nothing to notify about when there is no new status
        if (!($new instanceof ShopStatus) || empty($this->onBeforeStatusUpsertListeners)) {
            return;
        }
        // avoid recursion if a listener reads/writes the status again
        if ($this->firingOnBeforeStatusUpsert) {
            return;
        }

        $this->firingOnBeforeStatusUpsert = true;
        try {
            foreach ($this->onBeforeStatusUpsertListeners as $listener) {
                try {
                    call_user_func($listener, $current, $new);
                } catch (\Exception $e) {
                    // a third-party callback that fails must NEVER break status/token persistence
                    Logger::getInstance()->error('onBeforeStatusUpsert listener error: ' . $e->getMessage());
                } catch (\Throwable $e) {
                    // PHP 7+: Error types (TypeError, etc.) implement Throwable but not Exception
                    Logger::getInstance()->error('onBeforeStatusUpsert listener error: ' . $e->getMessage());
                }
            }
        } finally {
            $this->firingOnBeforeStatusUpsert = false;
        }
    }
}

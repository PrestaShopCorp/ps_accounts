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

namespace PrestaShop\Module\PsAccounts\Context;

use Context;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;

/**
 * Get the shop context
 */
class ShopContext
{
    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var UserTokenRepository
     */
    private $userTokenRepository;

    /**
     * @var Context
     */
    private $context;

    /**
     * ShopContext constructor.
     *
     * @param ConfigurationRepository $configuration
     * @param UserTokenRepository $userTokenRepository
     * @param Context $context
     */
    public function __construct(
        ConfigurationRepository $configuration,
        UserTokenRepository $userTokenRepository,
        Context $context
    ) {
        $this->configuration = $configuration;
        $this->userTokenRepository = $userTokenRepository;
        $this->context = $context;
    }

    /**
     * @return bool
     */
    public function isShop17()
    {
        return version_compare(_PS_VERSION_, '1.7.0.0', '>=');
    }

    /**
     * @return bool
     */
    public function isShop173()
    {
        return version_compare(_PS_VERSION_, '1.7.3.0', '>=');
    }

    /**
     * @return int
     */
    public function getShopContext()
    {
        return \Shop::getContext();
    }

    /**
     * ID of shop or group
     *
     * @return int|null
     */
    public function getShopContextId()
    {
        if (\Shop::getContext() == \Shop::CONTEXT_SHOP) {
            return \Shop::getContextShopID();
        }

        if (\Shop::getContext() == \Shop::CONTEXT_GROUP) {
            return \Shop::getContextShopGroupID();
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isShopContext()
    {
        if ($this->isMultishopActive() && \Shop::getContext() !== \Shop::CONTEXT_SHOP) {
            return false;
        }

        return true;
    }

    /**
     * is multishop active "right now"
     *
     * @return bool
     */
    public function isMultishopActive()
    {
        //return \Shop::isFeatureActive();
        return $feature_active = (bool) \Db::getInstance()->getValue('SELECT value FROM `' . _DB_PREFIX_ . 'configuration` WHERE `name` = "PS_MULTISHOP_FEATURE_ACTIVE"')
                && (\Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'shop') > 1);
    }

    /**
     * @return bool
     */
    public function sslEnabled()
    {
        return $this->configuration->sslEnabled();
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return false == $this->sslEnabled() ? 'http' : 'https';
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return ConfigurationRepository
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return UserTokenRepository
     */
    public function getUserToken()
    {
        return $this->userTokenRepository;
    }
}

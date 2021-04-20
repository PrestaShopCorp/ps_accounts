<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Context;

use Context;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

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
     * @var Context
     */
    private $context;

    /**
     * ShopContext constructor.
     *
     * @param ConfigurationRepository $configuration
     * @param Context $context
     */
    public function __construct(
        ConfigurationRepository $configuration,
        Context $context
    ) {
        $this->configuration = $configuration;
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
     * @return bool
     */
    public function isShopContext()
    {
        if (\Shop::isFeatureActive() && \Shop::getContext() !== \Shop::CONTEXT_SHOP) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isMultishopActive()
    {
        return \Shop::isFeatureActive();
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
}

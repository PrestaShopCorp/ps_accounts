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

use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Http\Controller\AbstractV2ShopRestController;

class ps_AccountsApiV2ShopProofModuleFrontController extends AbstractV2ShopRestController
{
    /**
     * @var bool
     */
    protected $authenticated = true;

    /**
     * @var ProofManager
     */
    private $proofManager;

    /**
     * @var StatusManager
     */
    private $statusManager;

    /**
     * @return array
     */
    public function getScope()
    {
        return [
            'shop.proof.read',
        ];
    }

    /**
     * @return array
     */
    public function getAudience()
    {
        return [
            'ps_accounts/' . $this->statusManager->getCloudShopId(),
        ];
    }

    public function __construct()
    {
        parent::__construct();

        $this->proofManager = $this->module->getService(ProofManager::class);
        $this->statusManager = $this->module->getService(StatusManager::class);
    }

    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return array
     */
    public function show(Shop $shop, array $payload)
    {
        $this->statusManager->invalidateCache();

        return [
            'proof' => $this->proofManager->getProof(),
        ];
    }
}

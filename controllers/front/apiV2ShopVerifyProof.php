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

use PrestaShop\Module\PsAccounts\Account\ManageProof;
use PrestaShop\Module\PsAccounts\Account\ShopIdentity;
use PrestaShop\Module\PsAccounts\Http\Controller\AbstractV2ShopRestController;

class ps_AccountsApiV2ShopVerifyProofModuleFrontController extends AbstractV2ShopRestController
{
    /**
     * @var ManageProof
     */
    private $manageProof;

    /**
     * @var ShopIdentity
     */
    private $shopIdentity;

    /**
     * @var bool
     */
    protected $authenticated = true;

    /**
     * @return array
     */
    protected function getScope()
    {
        return [
//            'shop.verify_proof',
        ];
    }

    /**
     * @return array
     */
    protected function getAudience()
    {
        return [];
    }

    public function __construct()
    {
        parent::__construct();

        $this->manageProof = $this->module->getService(ManageProof::class);
        $this->shopIdentity = $this->module->getService(ShopIdentity::class);
    }

    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return array
     */
    public function show(Shop $shop, array $payload)
    {
        $cloudShopId = $this->shopIdentity->getShopUuid();
        $this->assertAudience([
            'shop_' . $cloudShopId,
            //'https://accounts-api.distribution.prestashop.net/shops/' . $cloudShopId,
        ]);

        return [
            'proof' => $this->manageProof->getProof(),
        ];
    }
}

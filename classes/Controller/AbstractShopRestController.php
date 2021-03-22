<?php

namespace PrestaShop\Module\PsAccounts\Controller;

use PrestaShop\Module\PsAccounts\Exception\Http\NotFoundException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use Shop;

class AbstractShopRestController extends AbstractRestController
{
    /**
     * @var string
     */
    public $resourceId = 'shop_id';

    /**
     * @param int $id
     *
     * @return Shop
     */
    public function bindResource($id)
    {
        $shop = new Shop($id);

        if (!$shop->id) {
            throw new NotFoundException('Shop not found [' . $id . ']');
        }

        $this->setConfigurationShopId($shop->id);

        return $shop;
    }

    /**
     * @param $shopId
     *
     * @return void
     */
    protected function setConfigurationShopId($shopId)
    {
        /** @var ConfigurationRepository $conf */
        $conf = $this->module->getService(ConfigurationRepository::class);
        $conf->setShopId($shopId);
    }
}

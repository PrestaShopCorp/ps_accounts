<?php

namespace PrestaShop\Module\PsAccounts\Controller;

class AbstractShopRestController extends AbstractRestController
{
    /**
     * @var string
     */
    public $resourceId = 'shop_id';

    /**
     * @return array
     */
    protected function decodePayload()
    {
        $payload = parent::decodePayload();

        if (! array_key_exists($this->resourceId, $payload) ||
            ! is_integer($payload[$this->resourceId])) {
            $payload[$this->resourceId] = $this->context->shop->id;
        }

        return $payload;
    }
}

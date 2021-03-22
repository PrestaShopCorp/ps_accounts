<?php

namespace PrestaShop\Module\PsAccounts\Controller;

class AbstractRestChildController extends AbstractRestController
{
    /**
     * @var string
     */
    public $parentId = 'parent_id';

    /**
     * @return array
     */
    protected function decodePayload()
    {
        $payload = parent::decodePayload();

        if (!array_key_exists($this->parentId, $payload) ||
            !is_integer($payload[$this->parentId])) {
            $payload[$this->parentId] = $this->context->shop->id;
        }

        return $payload;
    }
}

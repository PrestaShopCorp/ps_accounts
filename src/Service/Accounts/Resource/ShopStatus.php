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

namespace PrestaShop\Module\PsAccounts\Service\Accounts\Resource;

use DateTime;
use PrestaShop\Module\PsAccounts\Http\Resource\Resource;

class ShopStatus extends Resource
{
    /**
     * @var string
     */
    public $cloudShopId;

    /**
     * @var bool
     */
    public $isVerified = false;

    /**
     * @var string
     */
    public $frontendUrl;

    /**
     * @var string
     */
    public $backOfficeUrl;

    /**
     * @var string
     */
    public $shopVerificationErrorCode;

    /**
     * @var string
     */
    public $pointOfContactUuid;

    /**
     * @var string
     */
    public $pointOfContactEmail;

    /**
     * @var DateTime|null
     */
    public $createdAt;

    /**
     * @var DateTime|null
     */
    public $updatedAt;

    /**
     * @var DateTime|null
     */
    public $verifiedAt;

    /**
     * @var DateTime|null
     */
    public $unverifiedAt;

    //TODO: Add multipleShopId from the cloud

    public function __construct($values = [])
    {
        $this->castDateTime($values, [
            'createdAt',
            'updatedAt',
            'verifiedAt',
            'unverifiedAt',
        ]);
        $this->castBool($values, [
            'isVerified',
        ]);

        parent::__construct($values);
    }

    /**
     * @param bool $all
     *
     * @return array
     */
    public function toArray($all = true)
    {
        $array = parent::toArray($all);

        $this->uncastDateTime($array, [
            'createdAt',
            'updatedAt',
            'verifiedAt',
            'unverifiedAt',
        ]);

        return $array;
    }
}

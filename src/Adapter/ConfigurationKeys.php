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

namespace PrestaShop\Module\PsAccounts\Adapter;

use PrestaShop\Module\PsAccounts\Type\Enum;

class ConfigurationKeys extends Enum
{
    const PSX_UUID_V4 = 'PSX_UUID_V4';
    const PS_ACCOUNTS_LOGIN_ENABLED = 'PS_ACCOUNTS_LOGIN_ENABLED';
    const PS_ACCOUNTS_OAUTH2_CLIENT_ID = 'PS_ACCOUNTS_OAUTH2_CLIENT_ID';
    const PS_ACCOUNTS_OAUTH2_CLIENT_SECRET = 'PS_ACCOUNTS_OAUTH2_CLIENT_SECRET';
    const PS_ACCOUNTS_ACCESS_TOKEN = 'PS_ACCOUNTS_ACCESS_TOKEN';
    const PS_ACCOUNTS_LAST_UPGRADE = 'PS_ACCOUNTS_LAST_UPGRADE';
    const PS_ACCOUNTS_SHOP_PROOF = 'PS_ACCOUNTS_SHOP_PROOF';
    const PS_ACCOUNTS_CACHED_SHOP_STATUS = 'PS_ACCOUNTS_SHOP_STATUS';
    const PS_ACCOUNTS_VALIDATION_LEEWAY = 'PS_ACCOUNTS_VALIDATION_LEEWAY';

    /** @deprecated  */
    const PS_ACCOUNTS_FIREBASE_ID_TOKEN = 'PS_ACCOUNTS_FIREBASE_ID_TOKEN';
    /* @deprecated */
    const PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN = 'PS_ACCOUNTS_FIREBASE_REFRESH_TOKEN';
    /* @deprecated */
    const PS_ACCOUNTS_USER_FIREBASE_ID_TOKEN = 'PS_ACCOUNTS_USER_FIREBASE_ID_TOKEN';
    /* @deprecated */
    const PS_ACCOUNTS_USER_FIREBASE_REFRESH_TOKEN = 'PS_ACCOUNTS_USER_FIREBASE_REFRESH_TOKEN';
    /* @deprecated */
    const PS_ACCOUNTS_USER_FIREBASE_UUID = 'PS_ACCOUNTS_USER_FIREBASE_UUID';
    /* @deprecated */
    const PS_ACCOUNTS_FIREBASE_EMAIL = 'PS_ACCOUNTS_FIREBASE_EMAIL';
    /* @deprecated */
    const PS_ACCOUNTS_EMPLOYEE_ID = 'PS_ACCOUNTS_EMPLOYEE_ID';
    /* @deprecated  */
    const PS_CHECKOUT_SHOP_UUID_V4 = 'PS_CHECKOUT_SHOP_UUID_V4';
    /* @deprecated  */
    const PS_PSX_FIREBASE_ID_TOKEN = 'PS_PSX_FIREBASE_ID_TOKEN';
    /* @deprecated */
    const PS_PSX_FIREBASE_REFRESH_TOKEN = 'PS_PSX_FIREBASE_REFRESH_TOKEN';
    /* @deprecated */
    const PS_PSX_FIREBASE_REFRESH_DATE = 'PS_PSX_FIREBASE_REFRESH_DATE';
    /* @deprecated */
    const PS_PSX_FIREBASE_EMAIL = 'PS_PSX_FIREBASE_EMAIL';
}

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

namespace PrestaShop\Module\PsAccounts\AccountLogin;

use PrestaShop\Module\PsAccounts\Type\Enum;

/**
 * Transient session keys written during the OAuth2 login flow. Single source of
 * truth: any new transient key added here is automatically cleared by
 * OAuth2LoginTrait::clearOAuth2SessionState() via Enum::values().
 *
 * Keep this enum scoped to keys that must be purged at the end of a flow. A
 * non-transient key (e.g. loginError) must NOT be listed here, or it would be
 * wrongly removed by clearOAuth2SessionState().
 */
class OAuth2SessionKeys extends Enum
{
    const STATE = 'oauth2state';
    const PKCE_CODE = 'oauth2pkceCode';
    const ACTION = 'oauth2action';
    const SOURCE = 'source';
    const SHOP_ID = 'shopId';
    const FORCE_SIGNUP = 'forceSignup';
    const RETURN_TO = 'return_to';
}

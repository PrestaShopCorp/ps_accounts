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

return [
    'ps_accounts.environment' => 'integration',
    'ps_accounts.accounts_api_url' => 'https://accounts-api-prestabulle2.distribution-integration.prestashop.net/',
    'ps_accounts.accounts_ui_url' => 'https://accounts-prestabulle2.distribution-integration.prestashop.net',
    'ps_accounts.sso_account_url' => 'https://prestashop-newsso-staging.appspot.com/login',
    'ps_accounts.sso_resend_verification_email_url' => 'https://prestashop-newsso-staging.appspot.com/account/send-verification-email',
    'ps_accounts.billing_api_url' => 'https://billing-api.distribution-preprod.prestashop.net/',
    'ps_accounts.indirect_channel_api_url' => 'https://indirect-channel-api-preprod.prestashop.net',
    'ps_accounts.sentry_credentials' => 'https://a065bd1f092f8c849e6076fe0640d049@o298402.ingest.us.sentry.io/5354585',
    'ps_accounts.segment_write_key' => 'eYODaH20rT1lMRTTUtAa15BKBlV1XUXQ',
    'ps_accounts.accounts_vue_cdn_url' => 'https://unpkg.com/prestashop_accounts_vue_components@3/dist/psaccountsVue.umd.min.js',
    'ps_accounts.accounts_cdn_url' => 'https://unpkg.com/prestashop_accounts_vue_components@5',
    'ps_accounts.svc_accounts_ui_url' => 'https://accounts.psessentials-integration.net',
    'ps_accounts.oauth2_url' => 'https://oauth-integration.prestashop.com',
    'ps_accounts.token_audience' => 'https://accounts-api.distribution-integration.prestashop.net',
    //ps_accounts.oauth2_url_authorize' => 'https://oauth-integration.prestashop.com/oauth2/auth'
    //ps_accounts.oauth2_url_access_token' => 'https://oauth-integration.prestashop.com/oauth2/token'
    //ps_accounts.oauth2_url_resource_owner_details' => 'https://oauth-integration.prestashop.com/userinfo'
    //ps_accounts.oauth2_url_session_logout' => 'https://oauth-integration.prestashop.com/oauth2/sessions/logout'

    'ps_accounts.testimonials_url' => 'https://assets.prestashop3.com/dst/accounts/assets/testimonials.json',

    'ps_accounts.check_api_ssl_cert' => true,
    'ps_accounts.verify_account_tokens' => true,
];

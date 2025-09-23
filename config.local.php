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
    'ps_accounts.environment' => 'development',
    'ps_accounts.accounts_api_url' => 'https://accounts-api.prestashop.local/',
    'ps_accounts.accounts_ui_url' => 'https://accounts.prestashop.local',
    'ps_accounts.sso_api_url' => 'https://auth.prestashop.local/api/',
    'ps_accounts.sso_account_url' => 'https://auth.prestashop.local/login',
    'ps_accounts.sso_resend_verification_email_url' => 'https://auth.prestashop.local/account/send-verification-email',
    'ps_accounts.billing_api_url' => 'https://billing-api.psessentials-integration.net',
    'ps_accounts.indirect_channel_api_url' => 'https://indirect-channel-api-integration.prestashop.net',
    'ps_accounts.sentry_credentials' => 'https://12e8e4574d50b54d878db8ee2c3f8380@o298402.ingest.us.sentry.io/5354585',
    'ps_accounts.segment_write_key' => 'UITzSdsFTgYsXaiJG09hsCiupUPwgJQB',
    //'ps_accounts.segment_write_key' => 'eYODaH20rT1lMRTTUtAa15BKBlV1XUXQ',
    'ps_accounts.check_api_ssl_cert' => false,
    'ps_accounts.verify_account_tokens' => false,
    'ps_accounts.accounts_vue_cdn_url' => 'http://prestashop8.docker.localhost/upload/psaccountsVue.umd.min.js',
    // 'ps_accounts.accounts_cdn_url' => 'https://unpkg.com/@prestashopcorp/accounts-components@beta',
    // 'ps_accounts.accounts_cdn_url' => 'http://127.0.0.1:3010/psaccountsVue.js',
    'ps_accounts.accounts_cdn_url' => 'https://assets.prestashop3.com/accounts-components/1/psaccountsVue.js',
    'ps_accounts.cors_allowed_origins' => [
        'https://integration-assets.prestashop3.com',
        'https://preproduction-assets.prestashop3.com',
        'https://assets.prestashop3.com',
    ],

    // a page to display "Update Your Module" message
    'ps_accounts.svc_accounts_ui_url' => 'https://accounts.prestashop.local/',

    // OAuth2 setup
    'ps_accounts.oauth2_url' => 'https://oauth.prestashop.local',
    'ps_accounts.token_audience' => 'https://accounts-api.prestashop.local',

    'ps_accounts.testimonials_url' => 'https://assets.prestashop3.com/dst/accounts/assets/testimonials.json',
    'ps_accounts.log_level' => 'INFO',
];

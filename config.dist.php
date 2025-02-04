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
    'ps_accounts.accounts_api_url' => 'https://accounts-api.prestashop.localhost/',
    'ps_accounts.accounts_ui_url' => 'https://accounts.prestashop.localhost',
    'ps_accounts.sso_api_url' => 'https://auth-preprod.prestashop.com/api/v1/',
    'ps_accounts.sso_account_url' => 'https://authv2-preprod.prestashop.com/login',
    'ps_accounts.sso_resend_verification_email_url' => 'https://auth-preprod.prestashop.com/account/send-verification-email',
    'ps_accounts.billing_api_url' => 'https://billing-api.psessentials-integration.net',
    'ps_accounts.sentry_credentials' => 'https://4c7f6c8dd5aa405b8401a35f5cf26ada@o298402.ingest.sentry.io/5354585',
    'ps_accounts.segment_write_key' => 'UITzSdsFTgYsXaiJG09hsCiupUPwgJQB',
    'ps_accounts.check_api_ssl_cert' => false,
    'ps_accounts.verify_account_tokens' => false,
    'ps_accounts.accounts_vue_cdn_url' => 'https://unpkg.com/prestashop_accounts_vue_components@3/dist/psaccountsVue.umd.min.js',
    'ps_accounts.accounts_cdn_url' => 'https://unpkg.com/prestashop_accounts_vue_components@4/dist/psaccountsVue.umd.min.js',
    'ps_accounts.environment' => 'development',

    // a page to display "Update Your Module" message
    'ps_accounts.svc_accounts_ui_url' => 'https://accounts.psessentials-integration.net',

    // OAuth2 configuration url
    'ps_accounts.oauth2_url' => 'https://oauth.prestashop.localhost',

    // Login page testimonials url
    'ps_accounts.testimonials_url' => 'https://assets.prestashop3.com/dst/accounts/assets/testimonials.json',

    // optional log level (defaults to ERROR)
    //ps_accounts.log_level' => !php/const PrestaShop\Module\PsAccounts\Log\Logger::ERROR
    'ps_accounts.log_level' => 'INFO',
];

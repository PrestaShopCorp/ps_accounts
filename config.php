<?php

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
    '#ps_accounts.segment_write_key' => 'UITzSdsFTgYsXaiJG09hsCiupUPwgJQB',
    'ps_accounts.segment_write_key' => 'eYODaH20rT1lMRTTUtAa15BKBlV1XUXQ',
    'ps_accounts.check_api_ssl_cert' => false,
    'ps_accounts.verify_account_tokens' => false,
    'ps_accounts.accounts_vue_cdn_url' => 'http://prestashop8.docker.localhost/upload/psaccountsVue.umd.min.js',
    //ps_accounts.accounts_cdn_url' => 'http://prestashop8.docker.localhost/upload/psaccountsVue.js'
    //ps_accounts.accounts_cdn_url' => 'https://unpkg.com/prestashop_accounts_vue_components@5.1.0-test-1/dist/psaccountsVue.js'
    'ps_accounts.accounts_cdn_url' => 'http://localhost:5174/dist/psaccountsVue.js',

    // a page to display "Update Your Module" message
    'ps_accounts.svc_accounts_ui_url' => 'https://accounts.prestashop.local/',

    // OAuth2 setup
    'ps_accounts.oauth2_url' => 'https://oauth.prestashop.local',

    'ps_accounts.testimonials_url' => 'https://assets.prestashop3.com/dst/accounts/assets/testimonials.json',
    'ps_accounts.log_level' => 'DEBUG',
];

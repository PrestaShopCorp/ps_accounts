export type HealthCheck = {
  shopLinked: Boolean; // UUID shop & user e-mail
  isSsoEnabled: Boolean; // PS_ACCOUNTS_LOGIN_ENABLED
  oauthToken: {
    issuer: string; // From token
    issuedAt: number; // Timestamp
    expireAt: number; // Timestamp
    isExpired: boolean;
  };
  firebaseOwnerToken: {
    issuer: string; // From token
    issuedAt: number; // Timestamp
    expireAt: number; // Timestamp
    isExpired: boolean;
  };
  firebaseShopToken: {
    issuer: string; // From token
    issuedAt: number; // Timestamp
    expireAt: number; // Timestamp
    isExpired: boolean;
  };
  fopenActive: Boolean; // is php fopen active
  curlActive: Boolean; // is php curl active
  oauthApiConnectivity: Boolean; // can the shop retrieve the .well-known
  accountsApiConnectivity: Boolean; // can the shop retrieve the accounts-api healthcheck
  serverUTC: number; // Timestamp
  mysqlUTC: number; // Timestamp
  publicKeyShasum?: string; // La shasum sha1 de la clef publique du marchand
  env: {
    oauth2Url: string; // config.yml => ps_accounts.oauth2_url
    accountsApiUrl: string; // config.yml => ps_accounts.accounts_api_url
    accountsUiUrl: string; // config.yml => ps_accounts.accounts_ui_url
    accountsCdnUrl: string; // config.yml => ps_accounts.accounts_cdn_url
    testimonialsUrl: string; // config.yml => ps_accounts.testimonials_url
    checkApiSslCert: string; // config.yml => ps_accounts.check_api_ssl_cert
  };
};

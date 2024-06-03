# Change Log

## [7.0.0] - 2024-04-22

### Added
* OAuth2 client for every linked shop will be used for token generation
* Introducing internal CommandBus
* Specialized Hook Classes
* ServiceInjector for tests based on @inject tag
* support for `.well-known/openid-configuration` with a unique `ps_accounts.oauth2_url` in config.yml
* AdminLoginPsAccounts dedicated login page

### Changed
* Merge code of branches 6 & 5 into a unified version for 1.6, 1.7 and 8
* Unbound Shop Linked Status & Token Validity
* Generalized Circuit Breaker to all api calls
* Login with PrestaShop after linkshop requires to be activated explicitly
* AdminLogin override replaced with a redirect to a dedicated AdminLoginPsAccounts page 
* Deprecated UserTokenRepository & ShopTokenRepository in favor of  
  Account\Session\Firebase\OwnerSession & Account\Session\Firebase\ShopSession
* Dependency scoping for collision mitigation
* Makefile refactoring : module bundle targets for CI & add php-scoper
* Reworked login page without inheritance from AdminLogin
* Stop installing Eventbus at install/reset
* Stop re-onboarding from v4 at install/reset

### Fixed
* Shop are unliked when firebase token expires
* Can't login again with PrestaShop using an account recreated after deletion in BO







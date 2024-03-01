# Change Log

## [7.0.1] - ?

### Changed
* Dependency scoping for collision mitigation
* Makefile refactoring : module bundle targets for CI & add php-scoper
* Reworked login page without inheritance from AdminLogin
* AdminLoginPsAccounts no more inherits from AdminLogin

## [7.0.0] - 2024-02-14

### Added
* OAuth2 shop client for token generation
* Introducing CommandBus
* Specialized Hook Classes
* ServiceInjector for tests based on @inject tag

### Changed
* Merge code of branches 6 & 5 into a unique version
* Unbound Shop Linked Status & Token Validity
* Generalized Circuit Breaker to all api calls
* No more auto-enable login with PrestaShop after linkshop
* Replace AdminLogin override with a dedicated AdminLoginPsAccounts page & internal redirect

### Fixed
* No more invalid tokens with Shop Oauth2 Client







# Change Log

## [7.0.1] - ?

### Changed
* Dependency scoping for collision mitigation
* Makefile refactoring : module bundle targets for CI & add php-scoper
* Reworked login page without inheritance from AdminLogin
* Stop installing Eventbus at install/reset
* Stop re-onboarding from v4 at install/reset

### Added
* AdminLoginPsAccounts dedicated login page

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
* Login with PrestaShop after linkshop requires to be activated explicitly
* AdminLogin override replaced with a redirect to a dedicated AdminLoginPsAccounts page 

### Fixed
* Shop are unliked when firebase token expires
* Can't login again with PrestaShop using an account recreated after deletion in BO







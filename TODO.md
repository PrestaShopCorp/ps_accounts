## TODO
* create a HttpClientResponse not an "array"
* disseminate tests and filter on module build

[2024-03-11 14:34:49] request.CRITICAL: Uncaught PHP Exception Symfony\Component\ErrorHandler\Error\ClassNotFoundError: 
"Attempted to load trait "ArrayAccessorTrait" from namespace "PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Tool". 
Did you forget a "use" statement for another namespace?" at 
/var/www/html/modules/ps_accounts/vendor/league/oauth2-client/src/Provider/AbstractProvider.php line 40 
{"exception":"[object] (Symfony\\Component\\ErrorHandler\\Error\\ClassNotFoundError(code: 0): 
Attempted to load trait \"ArrayAccessorTrait\" from namespace \"PrestaShop\\Module\\PsAccounts\\Vendor\\League\\OAuth2\\Client\\Tool\".
\nDid you forget a \"use\" statement for another namespace? at 
/var/www/html/modules/ps_accounts/vendor/league/oauth2-client/src/Provider/AbstractProvider.php:40)"} []

# External dependencies
  * activate login PsAccountsService::enableLogin(true)

# #./tools/vendor/bin/autoindex prestashop:add:index ${TMP_DIR}
# Bug upgrade 8.1
# Bug ServiceContainer init on 1.6 (potentially critical)
# Bug Edition LoginPage broken CSS (padding top & broken when rebuilt)
# Test (EmployeeAccountRepository, Token, LinkShop)

### 
* Override (fichier vide & tab->delete()
* tester sans les namespace dans le composer

delete from ps_hook_module where id_module=280 and id_hook IN (178, 57);
select ps_module.id_module, ps_module.name, ps_hook_module.id_hook, ps_hook.name
from ps_hook_module
inner join ps_module on (ps_module.id_module=ps_hook_module.id_module)
inner join ps_hook on (ps_hook_module.id_hook=ps_hook.id_hook)
where ps_module.name='ps_accounts' and id_shop=1;

* Shouldn't we expose shop access token ?
* Define module's public API
* Remove symfony dep (standalone simple service container)
* ShopModuleUpdatedEvent duplicates (in memory OR db lock ?)
* UnlinkShop : method not allowed 405

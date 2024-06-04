## TODO
* create a HttpClientResponse not an "array"
* disseminate tests and filter on module build

# Bug upgrade 8.1
# Bug ServiceContainer init on 1.6 (potentially critical)
# Bug Edition LoginPage broken CSS (padding top & broken when rebuilt)

###
delete from ps_hook_module where id_module=280 and id_hook IN (178, 57);
select ps_module.id_module, ps_module.name, ps_hook_module.id_hook, ps_hook.name
from ps_hook_module
inner join ps_module on (ps_module.id_module=ps_hook_module.id_module)
inner join ps_hook on (ps_hook_module.id_hook=ps_hook.id_hook)
where ps_module.name='ps_accounts' and id_shop=1;

* Shouldn't we expose shop access token ?
* Define module's public API
* Remove symfony dep (standalone simple service container)
* UnlinkShop : method not allowed 405 (flashlight problem ?)

* Connectivity Check (wellKnown Red/Green)
* HealthCheck
* Throw Exception (or at least do not call api with empty/NullToken)
* ps9 cookie->* AdminLoginController breaking

* Activer le mode DEBUG
* allow_url_fopen = 
* CURLOPT_SSL_VERIFYPEER = false

* écrire ticket
* Amal

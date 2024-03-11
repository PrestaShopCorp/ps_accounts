## TODO
* create a HttpClientResponse not an "array"
* disseminate tests and filter on module build

## TEST
* interaction avec les PSXs 
  * checkout
  * facebook
  * billing
  * eventbus
  * shipping

# Tests
  * .md doc on Notion
  * ServiceInjector

# Marketplace
  * easy to fake module upgrade
    * 1.6
      * Install latest -1 & Tools::addonsRequest (upload local ./upload/ps_accounts.zip)
    * 1.7+
      * Install latest -1 & AddonsDataProvider::request (upload local ./upload/ps_accounts.zip)

# External dependencies
  * activate login PsAccountsService::enableLogin(true)

### 
* Override (fichier vide & tab->delete()
* tester sans les namespace dans le composer
* _dev
  * login.css pété après un build
  * build bulles 1-6 & build _dev
* flashlight
* reset Password
* doc partie scoper

delete from ps_hook_module where id_module=280 and id_hook IN (178, 57);
select ps_module.id_module, ps_module.name, ps_hook_module.id_hook, ps_hook.name
from ps_hook_module
inner join ps_module on (ps_module.id_module=ps_hook_module.id_module)
inner join ps_hook on (ps_hook_module.id_hook=ps_hook.id_hook)
where ps_module.name='ps_accounts' and id_shop=1;

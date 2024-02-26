## TODO
* create a HttpClientResponse not an "array"
* disseminate tests and filter on module build

## TEST
* Version PrestaShop: 1.6.1.2 / 1.7.5.6 / 1.7.8.2 / 8
* Tests d'upgrade :
  * 5.6 -> 7.0.0
  * 6.3 -> 7.0.0
* Test upgrade et maintient de la connexion
  * une shop avec des tokens valides qui upgrade ne doit plus être déconnectée si les tokens sont supprimés
* tracking login edition
  * uniquement en 8
* interaction avec les PSXs 
  * checkout
  * facebook
  * billing
  * eventbus
  * shipping

### TO BE FIXED
* activate login PsAccountsService::enableLogin(true)
* Maj LCOBUCCI (require php 7.1 min ? OU phpscoper)
* Override (fichier vide & tab->delete()

### 
* tester sans les namespace dans le composer
* finir la nouvelle page de login
* scoper LCOBUCCI
* login.css pété après un build

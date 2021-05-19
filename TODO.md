* défricher multishop / ShopProvider

* cleanup unused methods
    * PsAccountsService
    * PsAccountsPresenter
    * Adapter/Configuration
    * dégager FirebaseClient et sa conf
    * config.yml
    * employee_id dans le presenter
    * Rename Services*Clients
    * Minimum de compat à Prévoir

* créer un ConfigurationService

* multiboutique:
    * ShopProvider::getShopTree -> (données linkShop, employeeId)
    * User Credentials
    * tracker les contextes d'appels multi
    * UpdateShopUrl
    
* tests intés

* rétrocompat
    
* compat:
    * v4 - v5 : chemin critique vers la page de config
        * onboarding obsolète -> panel warning -> maj données onboarding
        * presenter: 
            * isV4, isV5
            * isOnboardedV4/V5 
            * getOrRefreshTokenV4/V5
            * PsAccountsPresenterV4/V5
            
* dep v5 - maj vue_component

FIXME :
-------
* URLs presenter -> embraquer une config hybride 
* api/v1/
* emailVerified => requêter SSO

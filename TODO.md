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
    * ShopProvider::getShopTree
    * User Credentials
    * tracker les contextes d'appels multi
    * UpdateShopUrl
    
* compat:
    * src (default v4)
    * v5/src
        * accès via la facade
        * api uniquement sur la v5
    * champs supplémentaires en BDD
        * mode v5 dégradé
            * USER_FIREBASE_ID_TOKEN/REFRESH_TOKEN/UUID, EMPLOYEE_ID
            * shop_token proviens du même Firebase : OK

# Changes v7

## Architectural changes

* Account domain related code added/refactored into `PrestaShop\Module\PsAccounts\Account` namespace
* deprecated UserTokenRepository, ShopTokenRepository are maintained and replaced with OwnerSession and ShopSession
* Added a Token wrapper and a NullToken for simpler interactions with JWT tokens
* Entity like classes (OwnerSession, ShopSession, LinkShop) provide data from current context shop for multishop environments.  
You can use `ShopContext::execInShopContext` to execute code within a specific shop context.
* Introduced a CommandBus to better express module's exposed features

## Migrate from v5 & v6

Shops linked on v5 & v6 with a valid shop firebase token (or refresh token) should stay connected.

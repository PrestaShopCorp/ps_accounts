# PRODUCT.md — ps_accounts

## Product role

PrestaShop Account (`ps_accounts`) is the **identity and authentication gateway** for the PrestaShop ecosystem. It identifies and verifies merchant stores, then issues OAuth2 tokens (`TokenV2`) that other modules and services use to authenticate against PrestaShop Cloud APIs.

It is a mandatory prerequisite for:
- PS Essentials (PS Checkout, PS Marketing with Google, PS Social, etc.)
- Partner modules integrating with PrestaShop Account ("Built For" programme)
- Module Back Office (MBO) direct purchase
- CloudSync store synchronization

---

## Users / personas

| Persona | Description |
|---------|-------------|
| Merchant (store owner / admin) | Installs and configures ps_accounts in their PrestaShop back office. Consumes the verified store status to access PS Cloud services. |
| Partner module developer | Integrates ps_accounts via `PsAccountsService` to obtain a `cloudShopId` and a valid `TokenV2` for their own API calls. |
| PrestaShop Cloud services | Downstream consumers of `store.identified` / `store.verified` webhook events (Svix) and Eventstore events — accounts-api, CloudSync, Billing, etc. |

---

## Key flows

### 1. Automatic store identification (at install / update)
At install or update time, the module creates a shop identity on accounts-api and triggers a `store.identified` webhook (Svix) and a `ShopIdentityCreatedEvent` (Eventstore). This assigns a `cloudShopId` to the store — required by all dependent modules.

### 2. Automatic store verification
Immediately after identification, the module attempts automatic verification. On success: `store.verified` webhook + `ShopVerifiedEvent`. On failure: fallback to manual verification. A previously-verified store that loses verification emits `store.unverified`.

### 3. Manual verification (fallback)
If automatic verification fails, the merchant can trigger a manual re-verification command from the module settings page or from any PSX (e.g. PS Checkout). On repeated failure, the merchant is directed to support.

### 4. User contact identification (optional for service operation)
Once the store is verified, the merchant can optionally sign in via PrestaShop SSO to register a contact email. This triggers `store.point-of-contact-identified` and `ShopPointOfContactSetEvent`. Store operation (token generation, PSX functionality) does **not** depend on this step — it is primarily for data/CRM purposes.

### 5. Account switch (contact info update)
A verified store can change its contact email at any time via SSO, without service interruption or the dissociation/reassociation flow required by previous versions.

### 6. URL mismatch detection and resolution
When the store's current domain diverges from the URL registered in PrestaShop Account services, a persistent alert is displayed. The merchant chooses between: (a) updating the URL on the existing identity (`store.url-changed`) or (b) creating a new identity (`store.identified`) — an irreversible action that requires reconfiguring dependent services.

### 7. OAuth2 token lifecycle (TokenV2)
The module manages the full OAuth2 client-credentials flow: obtains a `TokenV2` (JWT `access_token` + opaque `refresh_token`) from auth-hydra, stores it via `ConfigurationRepository`, and transparently refreshes it. Third-party modules access the current token through `PsAccountsService`.

---

## Business rules

- A store **must be identified before it can be verified**. Verification cannot be skipped.
- Service operation (PSX modules, CloudSync) depends on **verification**, not on user contact information.
- The `cloudShopId` is assigned at identification time and is **immutable** for the lifetime of a store identity.
- In a multistore instance, **each store is identified and verified independently**.
- Creating a new store identity is **irreversible** — existing subscriptions remain tied to the old identity and require support intervention to transfer.
- The `PsAccountsService` public API is **backwards-compatible** — no breaking changes without a major version bump and coordination with partner module maintainers.
- All vendor dependencies are **scoped** under `PrestaShop\Module\PsAccounts\Vendor\*` (php-scoper) to avoid namespace conflicts with other modules.
- Token storage and retrieval go exclusively through `ConfigurationRepository` — direct `ps_configuration` access is forbidden.

---

## Out of scope

- **Billing / subscription management** — handled by MBO; ps_accounts only provides the identity layer.
- **Module business logic** — ps_accounts does not implement functionality for PS Checkout, CloudSync, or any PSX; it only supplies the `cloudShopId` and `TokenV2` they require.
- **User account management** — account creation, password reset, and profile management are handled by PrestaShop SSO; ps_accounts only brokers the SSO authentication flow.
- **Direct database access outside the Repository layer** — no handler or service writes directly to `ps_configuration` or other tables.
- **UI/UX design** — back-office component rendering is delegated to the Vue 3 frontend in `_dev/apps/`; this document covers flows and rules, not screen layout.

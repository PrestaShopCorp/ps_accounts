# e2e testing ps_accounts

PS Accounts depends on a running testing PrestaShop environment.

## Running tests

First start e2e environment in `e2e-env` (see e2e-env [README.md](../e2e-env/README.md)) then run the tests:

```
cd ../e2e-env
docker compose up -d prestashop
cd ../e2e
pnpm test:e2e
```

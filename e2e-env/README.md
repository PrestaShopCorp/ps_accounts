# PS Accounts e2e environment

## Start the environment

1. Create your own configuration from the default values:

```shell
cp .env.dist .env
```

2. start docker environment:

```shell
docker compose up
```

Or in detached mode:

```shell
docker compose up -d
```

Or specifically only starting PrestaShop (and its dependencies) with special commands to be sure your containers and volumes will be recreacted/renewed:

```shell
docker compose up prestashop --force-recreate --renew-anon-volumes
```

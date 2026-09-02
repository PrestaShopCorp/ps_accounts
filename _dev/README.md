# ps_accounts — front

Sources TypeScript des deux apps front du module (`apps/login`, `apps/notifications`),
compilées par Vite en librairies ES vers `../views/js/` et `../views/css/`.

## Prérequis

Node **24** (voir `.nvmrc` à la racine du dépôt) et pnpm **10+**.

```
nvm use
```

## Installation

```
pnpm install --frozen-lockfile
```

## Build de production

Compile les deux apps. C'est ce que lance la CI de release et `make build-front`
depuis la racine.

```
pnpm run build
```

Chaque app peut être construite séparément :

```
pnpm run build:login
pnpm run build:notifications
```

## Lint

```
pnpm run lint
```

> ⚠️ `lint` et `lint:fix` sont actuellement inopérants : ESLint 9 attend une
> « flat config » (`eslint.config.js`) alors que le projet n'a qu'un
> `.eslintrc.js` hérité, et l'option `--ext` a été supprimée. La migration est
> à faire.

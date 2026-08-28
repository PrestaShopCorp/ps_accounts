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
pnpm run lint:fix
```

Configuration dans `eslint.config.mjs` (ESLint 9, « flat config »). Elle
partitionne le périmètre en trois : les sources `apps/**/*.ts` en contexte
navigateur, les `*.config.ts` de Vite en contexte Node (elles utilisent
`__dirname`), et les `*.js` de configuration en CommonJS.

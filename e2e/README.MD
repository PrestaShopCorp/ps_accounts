# 🛍️ PrestaShop Account — `run shops`

Cette documentation explique comment lancer des créations de shop de le dossier e2e de account

---

## ⚙️ Prérequis

1. Faire une demande de Credentials MyTun dans le chanel slack #team-platform-engineering
2. Récupère :
   - Ton **account tag** (ACCOUNT_TAG)
   - Ton **tunnel secret** (TUNNEL_SECRET) 
   - Ton **tunnel id** (TUNNEL_ID)
   - Ton **ps domaine** (PS_DOMAIN) 
   - Ton **domaine** (DOMAIN) 

---

##  AVANT TOUTE ACTION ALLEZ DANS LE BON DOSSIER: => DANS LE TERMINAL TAPER LA COMMANDE "CD e2e"

---

## 🛠️ Configuration

Crée un fichier `.env` à la racine du dossier build-Shop_with_Mytun avec ce contenu (exemple dans le .env.dist) :

```
ACCOUNT_TAG="your account tag"
TUNNEL_SECRET="your tunel secret"
TUNNEL_ID="your tunel id"
PS_DOMAIN="prestashop.firstname-name-mytun.prestashop.name"
DOMAIN="firstname-name-mytun.prestashop.name"

#Image flashlight
PS_VERSION=nightly-nginx 

#Image officiel
PS_VERSION=8.2.1

#Seconde image officel pour le multistore double instance
SECONDE_PS_VERSION==nightly

# Modules Version
PS_ACCOUNTS_VERSION=v7.2.2
```

🚨 Comme vous pouvez le voir, Flashlight et l’image officielle utilisent la même variable, mais ne pointent pas vers les mêmes images.
Il faut donc bien commenter ou décommenter selon la version que vous souhaitez utiliser.

---

## Modifier la version de PrestaShop

Pour changer la version, va sur https://hub.docker.com/r/prestashop/prestashop/tags

Puis, modifie la ligne dans docker-compose.yml :

```
image: prestashop/prestashop:**tag de la version souhaitée**

(ex: image: prestashop/prestashop:8.2-apache)
``` 

=>Tu peux adapter cela pour shop1, shop2, ou tout autre conteneur que tu ajoutes.

---

## Modifier la version de PrestaShop Flashlight

Pour changer la version, va sur https://hub.docker.com/r/prestashop/prestashop-flashlight/tags

Puis, modifie la ligne dans docker-compose.yml :

```
image: prestashop/prestashop-flashlight:**tag de la version souhaitée**

(ex: image: prestashop/prestashop-flashlight:nightly-nginx)
``` 

=>ATTENTION LE MULTISTORE NE FONCTIONNE PAS AVEC FLASLIGHT, MODIFIER UNIQUEMENT LE CONTAINER FLASHLIGHT

---

## Lancer une boutique (flashlight)

Dans le terminal lancer la Commande = 

```npm run build-shop```
      
Cette commande :
   - Lance une boutique PrestaShop Flashlight
   - Génère automatiquement les fichiers mytun-config.yml & mytun-credentials.yml pour exposer la boutique via MyTun
   - Démarre tous les conteneurs nécessaires en arrière-plan

URL d’accès à la boutique : https://ton_domaine/admin-dev

---

## Lancer une Image officiel

Dans le terminal lancer la Commande = 

```npm run build-shop -- "$PS_VERSION" "" "imageoff" "$ACCOUNT_VERSION"```
      
Cette commande :
   - Lance une seule boutique PrestaShop (shop1)
   - Génère automatiquement les fichiers mytun-config.yml & mytun-credentials.yml pour exposer la boutique via MyTun
   - Démarre tous les conteneurs nécessaires en arrière-plan

URL d’accès à la boutique : https://ton_domaine/admin-dev

🚨 Il faut commenter la ligne PHYSICAL_URI dans docker-compose.yml, si pas utilisé en multistore

---

## Lancer deux boutiques (MultiStore - Multi-Instance) NE FONTIONNE PAS AVEC FLASHLIGHT !! IMAGE PRESTASHOP/PRESTASHOP UNIQUEMENT

Dans le terminal lancer la Commande = 

```npm run build-shop -- "$PS_VERSION" "SECONDE_PS_VERSION" "multistore" "$ACCOUNT_VERSION"```

Cette commande :
   - Active deux boutiques PrestaShop (shop1 et shop2)
   - Active deux boutiques en multi-instance, donc deux BDD sont créées
   - Génère les fichiers mytun-config.yml & mytun-credentials.yml pointant vers le reverse proxy nginx_proxy
   - Utilise un reverse proxy Nginx pour rediriger /shop1 et /shop2
   - Démarre tous les conteneurs nécessaires en arrière-plan

URL d’accès à la boutique : https://ton_domaine/shop1/admin-dev et https://ton_domaine/shop2/admin-dev

🚨 Il faut décommenter les ligne PHYSICAL_URI dans docker-compose.yml, si ce n'ai pas fait, dans shop1 et shop2

---

## Nettoyer l’environnement

🚨 Lorsque on lance un npm run build-shop un recreate ce lance (--force-recreate -d), donc il n'est pas utile de kill une shop pour créer un nouvelle shop, cependant si c'est le cas vous pouvez lancer la commande si dessous.

Dans le terminal lancer la Commande = 

```npm run kill-shop```

Cette commande :
   - Arrête tous les conteneurs Docker (monostore, multistore, flashlight)
   - Supprime les volumes associés

---
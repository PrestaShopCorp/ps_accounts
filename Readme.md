# Prestashop Account

## Install project

Configure env :
```bash
make init
```

Clone the repo if not already done
```bash
git clone git@github.com:PrestaShopCorp/prestashop_accounts_vue_components.git
git clone github.com:PrestaShopCorp/prestashop_accounts_auth.git
```

/!\ Customize .env with path of dependencies

Customize docker-compose.override.yml for choice port

## Run project

List all Makefile rules
```bash
make help
```

Start project :
```bash
make start
```

Wait container be to state `healthy`

For watch docker-compose status run `watch docker-compose ps`

![](doc/healthy.png)

## How to connect ?

#### FRONT
```
FRONT URL   : http://localhost:<port>
User        : pub@prestashop.com
Pwd         : 123456789
```

#### BO
```
http://localhost:<port>/admin-dev/index.php/module/manage?email=demo@prestashop.com password=prestashop_demo
```
example
```
http://localhost:80/admin-dev/index.php/module/manage?email=demo@prestashop.com password=prestashop_demo
```

authentication
```
URL         : http://localhost:<port>/admin-dev
User        : demo@prestashop.com
Pwd         : prestashop_demo
```

#### DB
```
host        : localhost
port        : <port>
name        : prestashop
user        : macfly
password    : admin
prefix      : ps_
```

## Troubleshoot
error : `* Another setup is currently running...` patch `rm install.lock`

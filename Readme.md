# PrestaShop Account

## Run project

configure env :
```
make init
```

Start project :  
```
make start
```

Wait container be to state `healthy`  

For watch docker-compose status run `watch docker-compose ps`    

![](doc/healthy.png)

## CI

CI trigger on pull request labeled 'quality assurance needed'

To set custom checkout branch , edit [custom-checkout-version](custom-checkout-version)

To set custom version accounts-auth-componenents, edit [package.json](_dev/package.json) :
``` 
"accounts-auth-componenents": "PrestaShopCorp/prestashop_accounts_auth#feature/xxx-my-branch-name"
```

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

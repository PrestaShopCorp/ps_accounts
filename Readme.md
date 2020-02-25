# Prestashop Account

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

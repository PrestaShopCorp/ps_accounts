#!/bin/bash

# remove install folder
cd /var/www/html && rm -rf ./install

if [ "$SHOP_NAME" ]; then
  mysql -h $DB_SERVER -P $DB_PORT -u $DB_USER -p$DB_PASSWD $DB_NAME \
    -e "UPDATE ps_configuration SET value='$SHOP_NAME' WHERE name='PS_SHOP_NAME'"
fi

################################
# SSL setup

if [ "$PS_ENABLE_SSL" ]; then
  # FIXME: PS_VERSION <= v1.6
  if [ "$PS_VERSION" ]; then
    mysql -h $DB_SERVER -P $DB_PORT -u $DB_USER -p$DB_PASSWD $DB_NAME \
      -e "INSERT INTO ps_configuration (name, value, date_add, date_upd) VALUES ('PS_SSL_EVERYWHERE', '1', CURRENT_DATE, CURRENT_DATE)"
    mysql -h $DB_SERVER -P $DB_PORT -u $DB_USER -p$DB_PASSWD $DB_NAME \
      -e "UPDATE ps_configuration set value='1' WHERE name='PS_SSL_ENABLED'"
    mysql -h $DB_SERVER -P $DB_PORT -u $DB_USER -p$DB_PASSWD $DB_NAME \
      -e "INSERT INTO ps_configuration (name, value, date_add, date_upd) VALUES ('PS_SSL_ENABLED_EVERYWHERE', '1', CURRENT_DATE, CURRENT_DATE)"
  fi
  # FIXME: PS_VERSION >= v9
  if [ "$PS_VERSION" ]; then
    if [ -f .env ]; then
      if [ "$PS_TRUSTED_PROXIES" ]; then
        # starting with prestashop v9
        export TRUSTED_PROXIES=`echo $PS_TRUSTED_PROXIES | sed 's@/@\\\/@g'`
        sed -i -e "s/\(PS_TRUSTED_PROXIES=\).*/\1${TRUSTED_PROXIES}/" .env
      fi
    fi
  fi
fi

################################
# Physical URI setup

if [ "$PHYSICAL_URI" ]; then
  cd /var/www
  # fix htaccess
  sed -i -e "s/\[E=REWRITEBASE:[^\]]*\]/[E=REWRITEBASE:\/${PHYSICAL_URI}\/]/" ./html/.htaccess
  # move into subdirectory
  mv html $PHYSICAL_URI && mkdir html && mv $PHYSICAL_URI html/
  # update physical uri
  mysql -h $DB_SERVER -P $DB_PORT -u $DB_USER -p$DB_PASSWD $DB_NAME \
    -e "UPDATE ps_shop_url SET physical_uri='/$PHYSICAL_URI/'"
fi

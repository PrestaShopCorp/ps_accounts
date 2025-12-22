#!/bin/bash

# remove install folder
cd /var/www/html && rm -rf ./install

if [ -f .env ]; then
  if [ "$PS_TRUSTED_PROXIES" ]; then
    # starting with prestashop v9
    export TRUSTED_PROXIES=`echo $PS_TRUSTED_PROXIES | sed 's@/@\\\/@g'`
    sed -i -e "s/\(PS_TRUSTED_PROXIES=\).*/\1${TRUSTED_PROXIES}/" .env
  fi
fi

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


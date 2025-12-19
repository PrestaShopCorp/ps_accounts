#!/bin/bash

chown -R www-data:www-data /tmp/pre-install-scripts
chmod -R +x /tmp/pre-install-scripts
chown -R www-data:www-data /tmp/post-install-scripts
chmod -R +x /tmp/post-install-scripts

if [ "$DB_NAME" ]; then
  # create database if not exists
  echo "Creating database : $DB_NAME ..."
  mysql -h $DB_SERVER -P $DB_PORT -u root -p$DB_PASSWD \
    -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME}"

  # granting everything
  echo "Granting everything to : $DB_USER ..."
  mysql -h $DB_SERVER -P $DB_PORT -u root -p$DB_PASSWD \
    -e "GRANT all ON ${DB_NAME}.* to ${DB_USER}@'%' IDENTIFIED BY '${DB_PASSWD}'"
fi

#!/bin/bash

umask 000

########### Docker compose Profile variable ###########
PROFILE=${1:-shop}

########### credentials.json file creation ###########
# Set credentials in json
credentials_json_content='{
  "AccountTag": "'"$ACCOUNT_TAG"'",
  "TunnelSecret": "'"$TUNNEL_SECRET"'",
  "TunnelID": "'"$TUNNEL_ID"'"
}'

# Print in file
echo "$credentials_json_content" > "./myTun/config/mytun-credentials.json"
echo "credentials.json file created !"

############ Create config.yml file ###########
touch "./myTun/config/mytun-config.yml"

if [ "$PROFILE" = "multistore" ]; then
  config_yaml_content="tunnel: \"$TUNNEL_ID\"
credentials-file: /credentials.json
ingress:
  - hostname: \"$PS_DOMAIN\"
    service: http://nginx_proxy:80
  - service: http_status:404"
else
  config_yaml_content="tunnel: \"$TUNNEL_ID\"
credentials-file: /credentials.json
ingress:
  - hostname: \"$PS_DOMAIN\"
    service: http://prestashop:80
    originRequest:
      httpHostHeader: \"$PS_DOMAIN\"
  - service: http_status:404"
fi
# Print in file
echo "$config_yaml_content" > "./myTun/config/mytun-config.yml"
echo "config.yml file created !"

echo "Configuration applied for profile: $PROFILE"
#!/bin/bash
# Vérifier si le fichier .env existe et charger les variables d'environnement
if [ -f ".env" ]; then
    source .env
fi

# Vérifier si la variable DOMAIN est définie
if [ -n "$DOMAIN" ]; then
    echo "La variable DOMAIN est définie à partir de l'environnement."
else
    echo "La variable DOMAIN n'est pas définie dans l'environnement."
    exit 1
fi

# Définition des variables
randomPart=$RANDOM
shopVersion=$1
shopVersionSecondeShop=$2
profile=${3:-flashlight}
psDomain="${randomPart}.${DOMAIN}"
psAccountsVersion="${PS_ACCOUNTS_VERSION}"
accountTag="${ACCOUNT_TAG}"
tunnelSecret="${TUNNEL_SECRET}"
tunnelId="${TUNNEL_ID}"
# Ping de l'URL
appUrl="https://$psDomain"

#Verifie qu'on a bien deux version de shop en multistore
if [ "$profile" = "multistore" ]; then
  if [ -z "$shopVersionSecondeShop" ]; then
    echo "❌ En mode multistore, tu dois fournir une deuxième version de shop."
    exit 1
  fi
else
  shopVersionSecondeShop=""
fi

# Exécution de la commande make
makeFilePath='./'
makeCommand="make -C $makeFilePath docker-build PS_ACCOUNTS_VERSION=$psAccountsVersion PS_VERSION=$shopVersion SECONDE_PS_VERSION=$shopVersionSecondeShop PS_DOMAIN=$psDomain TUNNEL_ID=$tunnelId TUNNEL_SECRET=$tunnelSecret ACCOUNT_TAG=$accountTag PROFILE=$profile"
eval $makeCommand

# Fonction pour ping l'URL
ping_url() {
  local url=$1
  local timeout_duration=120
  local start_time=$(date +%s)

  if [ -z "$url" ]; then
    echo "Error: No URL specified."
    exit 1
  fi

  echo "Pinging URL: $url"

  while true; do
    # Calcul du temps écoulé
    local current_time=$(date +%s)
    local elapsed_time=$((current_time - start_time))

    # Vérifier si le délai est dépassé
    if [ $elapsed_time -ge $timeout_duration ]; then
      echo "Timeout: The URL did not respond within $timeout_duration seconds. Exiting."
      exit 1
    fi

    # Tester l'URL
    response=$(curl -o /dev/null -s -w '%{http_code}' "$url")
    if [ "$response" -eq 200 ] || [ "$response" -eq 302 ]; then
      echo "URL is reachable."
      return 0
    fi

    echo "Waiting for URL to be reachable... (elapsed: $elapsed_time seconds, last response: $response)"
    sleep 5
  done
}

ping_url $appUrl

cd ../e2e

if [[ "$OSTYPE" == "darwin"* ]]; then
sed -i '' "/^BASE_URL=/c\\
BASE_URL=${appUrl}/admin-dev/
" .env
sed -i '' "/^BASE_URL_FO=/c\\
BASE_URL_FO=${appUrl}
" .env
else
sed -i "/^BASE_URL=/c\\BASE_URL=${appUrl}/admin-dev/" .env
sed -i "/^BASE_URL_FO=/c\\BASE_URL_FO=${appUrl}" .env
fi

echo "Tests environment is available at: $appUrl/admin-dev/"

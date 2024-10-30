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
currentDate=$(date +%s)
shopVersion=$1
psDomain="${currentDate}.${DOMAIN}"
psAccountsVersion="${PS_ACCOUNTS_VERSION}"
# Ping de l'URL
appUrl="https://$psDomain"

# Exécution de la commande make
makeFilePath='./'
makeCommand="make -C $makeFilePath docker-build PS_ACCOUNTS_VERSION=$psAccountsVersion PS_VERSION=$shopVersion PS_DOMAIN=$psDomain"
eval $makeCommand

# Fonction pour ping l'URL
ping_url() {
  url=$1
  while true; do
    response_code=$(curl -s -o /dev/null -w "%{http_code}" $url)
    if [ $response_code -eq 200 ]; then
      break
    fi
    sleep 5
  done
}

ping_url $appUrl

# cd ../e2e
# # Ajouter ou écraser les variables dans le fichier .env
# sed -i '' "/^BASE_URL=/c\\
# BASE_URL=${appUrl}/admin-dev/
# " .env
# sed -i '' "/^BASE_URL_FO=/c\\
# BASE_URL_FO=${appUrl}
# " .env


# echo "Tests environment is available at: $appUrl/admin-dev/"

#!/bin/bash

export PS_ACCOUNTS_VERSION="v7.2.2"

build_shop() {
  local ps_version="$1"
  sleep 4
  npm run build-shop -- "$ps_version" "" "" "$PS_ACCOUNTS_VERSION"
  sleep 4
}

if [ -n "$1" ]; then
  SHOP_VERSIONS=("$1")
else
SHOP_VERSIONS=(
  # nightly-nginx    
  8.2.0-8.1-fpm-alpine
  # 1.7.8.8-7.4-fpm-alpine
  # 1.6.1.24-7.1-fpm-alpine
)
fi

for PS_VERSION in "${SHOP_VERSIONS[@]}"; do
  if [[ "$PS_VERSION" == 8.* ]]; then
    echo "PS8 detected ($PS_VERSION)"

    # Flow A: Associaiton + Delete Token
    build_shop "$PS_VERSION"
    npx playwright test --project="Account TNR V7" 01_multistore_activation_and_associaiton.spec.ts
    npx playwright test --project="Account TNR V7" 02_delete_tokens_and_change_uri.spec.ts

    # Flow B: Associaiton + Dissacociation
    build_shop "$PS_VERSION"
    npx playwright test --project="Account TNR V7" 01_multistore_activation_and_associaiton.spec.ts
    npx playwright test --project="Account TNR V7" 03_multistore_disassociation.spec.ts
  else
    echo "Non-PS8 detected ($PS_VERSION): using standard flow"
    build_shop "$PS_VERSION"
    npx playwright test --project="Account TNR V7" 01_multistore_activation_and_associaiton.spec.ts
    npx playwright test --project="Account TNR V7" 02_delete_tokens_and_change_uri.spec.ts
    npx playwright test --project="Account TNR V7" 03_multistore_disassociation.spec.ts
  fi

#Create the allure result directory
mkdir -p "allure-results-$PS_VERSION"

#Modify the json for the running version
for file in allure-results/*.json; do 
  jq 'if has("labels") then .labels[] |= if .name == "parentSuite" then .value |= . + "-PS_Version_'$PS_VERSION'" else . end else . end 
    | if has("testCaseId") then .testCaseId |= . + "-'$PS_VERSION'" else . end 
    | if has("historyId") then .historyId |= . + "-'$PS_VERSION'" else . end' "$file" > "$file.tmp" && mv "$file.tmp" "$file"
done

#Move allure-results to allure-results of the running version
cp -r allure-results/* "allure-results-$PS_VERSION"/ 

#Delete allure results directory
rm -rf allure-results/*
done

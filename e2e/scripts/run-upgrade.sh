#!/bin/bash

if [ -n "$1" ]; then
  SHOP_VERSIONS=("$1")
else
SHOP_VERSIONS=(
  1.6.1.24-alpine-nginx
  1.7.8.7-7.4-fpm-alpine
  8.2.0-8.1-fpm-alpine
  nightly-nginx
)
fi
if [ -n "$2" ]; then
  ACCOUNTS_VERSIONS=("$2")
else
ACCOUNTS_VERSIONS=(
  v5.6.2
  v6.3.1
  v7.1.0
  v7.1.2
)
fi

for index in "${!SHOP_VERSIONS[@]}"; do
  PS_VERSION="${SHOP_VERSIONS[$index]}"
  ACCOUNT_VERSION="${ACCOUNTS_VERSIONS[$index]}"

#Build the shop 
npm run build-shop -- "$PS_VERSION" "" "" "$ACCOUNT_VERSION"

#Run the tests
HEADLESS=false npx playwright test 01_front_check_upgrade.spec.ts || true

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
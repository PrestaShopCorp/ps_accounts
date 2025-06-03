#!/bin/bash

SHOP_VERSIONS=(
  # 1.7.8.7-7.4-fpm-alpine
  # 1.6.1.24-7.1-fpm-alpine
  # 1.7.8.11-7.4-fpm-alpine
  # 1.7.8.8-7.4-fpm-alpine
  # 8.1.0-8.1-fpm-alpine
  # 8.1.1-8.1-fpm-alpine
  # 8.1.5-8.1-fpm-alpine
  8.2.0-8.1-fpm-alpine
  nightly-nginx
)

for PS_VERSION in "${SHOP_VERSIONS[@]}"; do
#Build the shop 
npm run build-shop -- "$PS_VERSION"

#Run the tests
npx playwright test back front || true 

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
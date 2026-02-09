#!/bin/bash

export PS_ACCOUNTS_VERSION="v8.0.9"

if [ -n "$1" ]; then
  SHOP_VERSIONS=("$1")
else
SHOP_VERSIONS=(
  9.0.2-apache
  8.2.0-8.1
  1.7.8.8-7.4
)
fi

for PS_VERSION in "${SHOP_VERSIONS[@]}"; do
#Build the shop 
sleep 4
npm run build-shop -- "$PS_VERSION" "" "imageoff" "$PS_ACCOUNTS_VERSION"
sleep 4

#Run the tests
npx playwright test --project="Account TNR V8" module_installation || true
npx playwright test --project="Account TNR V8" 01_shop_verification.spec.ts || true
npx playwright test --project="Account TNR V8" point_of_contact|| true
npx playwright test --project="Account TNR V8" 02_manual_shop_verification.spec.ts|| true
npx playwright test --project="Account TNR V8" 03_verification_failed.spec.ts|| true

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
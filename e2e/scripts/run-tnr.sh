#!/bin/bash

SHOP_VERSIONS=("$1")

npm run build-shop -- "$PS_VERSION"

#Run the tests
npx playwright test module_installation || true 
npx playwright test 01_front_check_association.spec.ts || true
npx playwright test 02_front_check_disassociation.spec.ts || true
npx playwright test 03_multistore_activation_and_associaiton.spec.ts || true 
npx playwright test 04_multistore_disassociation.spec.ts || true

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
#!/bin/bash

filename=$1
environment=$2

rootDir="."
dist=${rootDir}/dist
configFile=${rootDir}/config/config.yml

module=$(grep '<name>' "${rootDir}/config.xml" | sed 's/^.*<name>\(.*\)<\/name>.*/\1/')
if [ -z "$module" ]; then
  echo "not a valid module"
  exit
fi

if [ -z "$filename" ]; then
  version=$(grep '<version>' "${rootDir}/config.xml" | sed 's/^.*\[CDATA\[\(.*\)\]\].*/\1/')
  branch=$(git -C "${rootDir}" branch | grep '\*' | sed 's/^\*\s\+\(.*\)$/\1/' | sed 's/\//\_/g')
  filename=${module}-${version}-${branch}.${environment}.zip
  filename=$(echo "$filename" | sed -E 's/[.]{2,}/./')
fi

function bundleModule () {
  echo "${filename} ..."
  cd $dist || return;
  rm -rf "./$module"
  mkdir "./$module"
  cp -pr -t "./$module" \
    ../config \
    ../controllers \
    ../scripts \
    ../sql \
    ../src \
    ../translations \
    ../upgrade \
    ../vendor \
    ../views \
    ../CHANGELOG.md \
    ../config.xml \
    ../index.php \
    ../LICENSE \
    ../logo.png \
    ../fix_upgrade.php \
    ../ps_accounts.php;
  rm -f "./$filename"
  zip -r "${filename}" "./$module" \
    -x \
    \*.git/* \
    \*.github/* \
    \*.build/* \
    \*.docker/* \
    \*.idea/* \
    \*tests/* \
    '*config/config.yml.*' \
    \*.bak/* \
    \*.md/* \
    '*composer.*' \
    '*Dockerfile.*' \
    '*Makefile' \
    '*/.*' \
    -q
}

function backupEnvironment () {
  if [[ -f $configFile ]]; then
    mv "$configFile" "${configFile}.bak"
  fi
}

function restoreEnvironment () {
  if [[ -f "${configFile}.bak" ]]; then
    mv "${configFile}".bak "$configFile"
  fi
}

if [ "$environment" ]; then
  echo "using provided environment [${environment}]"
  backupEnvironment
  cp "${configFile}"."${environment}" "$configFile"
  bundleModule
  restoreEnvironment
else
  bundleModule
fi


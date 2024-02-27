#!/bin/sh

filename=$1
environment=$2

rootDir="."
dist=${rootDir}/dist

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

echo "${filename} ..."

mkdir -p "./${dist}"
cd $dist || exit;

rm -rf "./$module"
mkdir "./$module"

cp -pr -t "./$module" \
  ../config \
  ../controllers \
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

if [ "$environment" ]; then
  configFileEnv="./${module}/config/config.yml.${environment}"
  if [ -f "$configFileEnv" ]; then
    echo "using provided environment [${environment}]"
    cp "$configFileEnv" "./${module}/config/config.yml"
  else
    echo "file not found [${configFileEnv}]"
  fi
fi

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


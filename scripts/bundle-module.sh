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

rm -rf "./${dist}/${module}"
mkdir -p "./${dist}/${module}"
cp -r $(cat .zip-contents) "./${dist}/${module}"
WORKDIR="${dist}" make autoindex

cd $dist || exit;

# switch request configuration env
if [ "$environment" ]; then
  configFileEnv="./${module}/config.${environment}.php"
  if [ -f "$configFileEnv" ]; then
    echo "using provided environment [${environment}]"
    cp "$configFileEnv" "./${module}/config.php"
  else
    echo "file not found [${configFileEnv}]"
  fi
fi

rm -f "./$filename"
zip -r "${filename}" "./$module" -q -x $(cat ../.zip-excludes)


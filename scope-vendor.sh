#!/bin/sh

VENDOR_DIRS="guzzlehttp league prestashopcorp"
SCOPED_DIR="vendor-scoped"
CURRENT_UID=$(id -u)
CURRENT_GID=$(id -g)

docker run -ti -v ${PWD}:/input -w /input -u ${CURRENT_UID}:${CURRENT_GID} \
  humbugphp/php-scoper:latest add-prefix --output-dir ${SCOPED_DIR} --force --quiet

for d in ${VENDOR_DIRS}; do
  rm -rf ./vendor/$d && mv ./${SCOPED_DIR}/$d ./vendor/;
done

rmdir ./${SCOPED_DIR}

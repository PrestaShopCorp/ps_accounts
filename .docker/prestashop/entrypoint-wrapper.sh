#!/bin/bash

# Remap www-data to the host user BEFORE the image entrypoint runs its
# `chown -R www-data:www-data /var/www/html`. That way the recursive chown
# (which cascades into the bind-mounted module) uses the host UID/GID directly
# and files on the host keep their original ownership.

if [ -n "$HOST_UID" ] && [ -n "$HOST_GID" ]; then
  echo "[entrypoint-wrapper] Remapping www-data to ${HOST_UID}:${HOST_GID}"
  groupmod -o -g "$HOST_GID" www-data 2>/dev/null || true
  usermod  -o -u "$HOST_UID" www-data 2>/dev/null || true
fi

exec docker-php-entrypoint /tmp/docker_run.sh

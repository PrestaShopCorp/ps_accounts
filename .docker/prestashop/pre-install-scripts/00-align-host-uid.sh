#!/bin/bash

# www-data has already been remapped to the host UID/GID by the entrypoint
# wrapper. The image just copied its baked install files from /tmp, restoring
# UID 33 ownership. Re-chown the install to the new www-data UID so the
# installer (running as www-data) can write, but skip the bind-mounted module
# (already host-owned; chowning across the mount is slow).

if [ -n "$HOST_UID" ] && [ -n "$HOST_GID" ]; then
  echo "Re-owning /var/www/html to ${HOST_UID}:${HOST_GID} (skipping module mount)..."
  find /var/www/html -path /var/www/html/modules/ps_accounts -prune -o \
    -exec chown -h "$HOST_UID:$HOST_GID" {} +
fi
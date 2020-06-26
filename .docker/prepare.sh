#!/bin/bash

echo "Apply patches"
cd /var/www/html
patch -p0 -f < /tmp/patches/ignore-checkout-17.diff || true

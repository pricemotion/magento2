#!/bin/bash

set -euo pipefail
cd /

usermod -u `stat -c %u /data/www` apache

mkdir -p /run/apache2
exec httpd -D FOREGROUND -f /etc/apache2/httpd.conf

#!/bin/bash

set -euo pipefail
cd /

usermod -u `stat -c %u /var/www/localhost/htdocs` apache

mkdir -p /run/apache2
exec httpd -D FOREGROUND -f /etc/apache2/httpd.conf

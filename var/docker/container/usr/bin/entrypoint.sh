#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

# It's time to link log file to stdout of pid 1 inside of container
unlink /var/www/logs/app.log || true
ln -s /proc/1/fd/0 /var/www/logs/app.log

# composer update
[ ! -d "/var/www/vendor/" ] &&  composer update

# Start supervisord
exec supervisord --configuration /etc/supervisor/supervisord.conf
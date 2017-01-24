#!/bin/bash
chown www-data:www-data /var/www/odm_data
chown -R www-data:www-data /var/www/html
chmod 777 /var/www/html/templates_c

exec /usr/sbin/apache2ctl -D FOREGROUND

#!/bin/bash
chown www-data:www-data /var/www/document_repository
chown -R www-data:www-data /var/www/html
chmod 777 /var/www/html/templates_c

TABLES_EXIST=$(mysql -u$DB_USER -p$DB_PASS -h$DB_HOST -P$DB_PORT $DB_NAME -e "SHOW TABLES LIKE 'odm_settings'" | grep "odm_settings" > /dev/null; echo "$?")

# Lets replace the default base_url with one from an ENV variable
sed -i "s/md5('admin')/md5('$ADMIN_PASSWORD')/g" /var/www/html/database.sql
# If the tables are not there, lets go ahead and import the default installation data
if [[ TABLES_EXIST -eq 1 ]]; then
        mysql -u $DB_USER --password=$DB_PASS --host=$DB_HOST -P$DB_PORT $DB_NAME < /var/www/html/database.sql
fi

exec /usr/sbin/apache2ctl -D FOREGROUND

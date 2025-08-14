#!/bin/bash
mkdir -p /var/www/document_repository
chown www-data:www-data /var/www/document_repository
chown -R www-data:www-data /var/www/html
mkdir -p /var/www/html/application/templates_c
chmod 777 /var/www/html/application/templates_c

# Create mail log file and set permissions
touch /var/log/mail.log
chown www-data:www-data /var/log/mail.log
chmod 644 /var/log/mail.log

# Configure mail service
if /configure-simple-mail.sh > /dev/null; then
    echo "Mail service configured"
else
    echo "Warning: Mail configuration had issues"
fi

TABLES_EXIST=$(mysql -u$APP_DB_USER -p$APP_DB_PASS -h$APP_DB_HOST -P$DB_PORT $APP_DB_NAME -e "SHOW TABLES LIKE 'odm_settings'" | grep "odm_settings" > /dev/null; echo "$?")

# Lets replace the default base_url with one from an ENV variable
sed -i "s/md5('admin')/md5('$ADMIN_PASSWORD')/g" /var/www/html/database.sql
# If the tables are not there, lets go ahead and import the default installation data
if [[ TABLES_EXIST -eq 1 ]]; then
        mysql -u $APP_DB_USER --password=$APP_DB_PASS --host=$APP_DB_HOST -P$DB_PORT $APP_DB_NAME < /var/www/html/database.sql
fi

exec /usr/sbin/apache2ctl -D FOREGROUND

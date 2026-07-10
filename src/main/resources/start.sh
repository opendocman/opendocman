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

exec /usr/sbin/apache2ctl -D FOREGROUND
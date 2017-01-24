FROM php:5.6-apache
MAINTAINER Logical Arts, LLC <info@logicalarts.net>

# Install packages
RUN apt-get update \
  && apt-get install -y apt-utils vim mysql-client php5-mysql git \
  && docker-php-ext-install pdo_mysql pdo

# Copy php configs
#COPY src/main/resources/docker-php-pecl-install /usr/local/bin/
#RUN docker-php-pecl-install xdebug-2.3.3
#COPY src/main/resources/xdebug.ini ${PHP_INI_DIR}/conf.d/docker-php-pecl-xdebug.ini
COPY src/main/resources/php.ini /usr/local/etc/php/conf.d

# Install mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html

# Change file permissions
RUN touch  /var/log/php_errors.log && \
  chown www-data:www-data  /var/log/php_errors.log && \
  usermod -u 1000 www-data

# Copy startup command
COPY src/main/resources/start.sh /start.sh
RUN chmod 755 /start.sh

EXPOSE 80

# By default, simply start apache.
ENTRYPOINT ["/start.sh"]

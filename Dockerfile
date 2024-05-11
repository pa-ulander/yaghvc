FROM php:8.3-apache

RUN apt update

# 1. development packages
RUN apt update && \
apt install -y --no-install-recommends \
apt-utils \
g++ \
git \
zip \
vim \
curl \
sudo \
unzip \
sqlite3 \
openssl \
ssl-cert \
apt-utils \
libzip-dev \
libicu-dev \
libbz2-dev \
libpng-dev \
libgmp-dev \
libwebp-dev \
libjpeg-dev \
libonig-dev \
libldap2-dev \
libmcrypt-dev \
libsqlite3-dev \    
libreadline-dev \
ca-certificates \
libfreetype6-dev \
libjpeg62-turbo-dev && \
docker-php-ext-configure gd --with-freetype=/usr/include/ --with-webp=/usr/include/ --with-jpeg=/usr/include/ && \
apt-get autoremove -y && \
apt-get clean && \
rm -rf /var/lib/apt/lists/*

RUN pecl install ast

# 2. apache configs
RUN echo "ServerName yagvc" >>/etc/apache2/apache2.conf
RUN mkdir "/etc/apache2/ssl"
RUN openssl req -x509 -nodes -days 36500 -newkey rsa:4096 -keyout /etc/apache2/ssl/selfsigned.key -out /etc/apache2/ssl/selfsigned.crt -subj "/C=AA/ST=AA/L=Internet/O=Docker/OU=tapeter/CN=selfsigned"

COPY ./docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public_html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 3. mod_rewrite for URL rewrite and mod_headers for .htaccess extra headers like Access-Control-Allow-Origin-
RUN a2enmod rewrite headers socache_shmcb ssl

# 4. start with base php config, then add extensions
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

EXPOSE 80
EXPOSE 443

RUN docker-php-ext-install \
gd \    
pdo \
zip \
exif \
bcmath \
opcache \
calendar \
pdo_mysql

# 5. composer
COPY --from=composer:2.7.2 /usr/bin/composer /usr/bin/composer

RUN pecl install xdebug-3.3.2 \
    && docker-php-ext-enable xdebug

# 6. we need a user with the same UID/GID as the host user
# so when we execute CLI commands, all the host file's permissions and ownership remains intact
# otherwise command from inside container will create root-owned files and directories
ARG uid
ARG devuser
RUN useradd -G www-data,root -u $uid -d /home/$devuser $devuser
RUN mkdir -p /home/$devuser/.composer
RUN chown -R $devuser:$devuser /home/$devuser

RUN DEFAULT_IGNORE_HTTPS_ERRORS=true

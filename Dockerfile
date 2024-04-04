FROM alpine:3.19.1

ENV TIMEZONE Stockholm/Berlin

# 1. development packages
RUN apk add --no-cache  --repository http://dl-cdn.alpinelinux.org/alpine/edge/community php
RUN apk update && apk upgrade
RUN apk add g++ \
    git \
    zip \
    vim \
    curl \
    wget \
    sudo \
    unzip \
    tzdata \
    openssl \
    apache2 \ 
    apache2-utils \
    php8.3-ctype \
    php8.3-mysqli \
    php8.3-mbstring \
    php8.3-pdo_mysql \
    php8.3-simplexml \
    php8.3-tokenizer \
    php8.3-opcache \
    php8.3-xdebug \
    php8.3-session \
    php8.3-apache2 \
    php8.3-mcrypt \
    php8.3-iconv \
    php8.3-phar \
    php8.3-zlib \
    php8.3-curl \
    php8.3-json \
    php8.3-intl \
    php8.3-apcu \
    php8.3-cli \
    php8.3-zip \
    php8.3-bz2 \
    php8.3-xml \
    php8.3-dom \
    php8.3-gd \
    ca-certificates \
    build-essential \
    docker-ce

# 2. apache configs
RUN echo "ServerName ${APP_NAME}" >> /etc/apache2/apache2.conf
RUN mkdir "/etc/apache2/ssl"
RUN openssl req -x509 -nodes -days 36500 -newkey rsa:4096 -keyout /etc/apache2/ssl/selfsigned.key -out /etc/apache2/ssl/selfsigned.crt -subj "/C=AA/ST=AA/L=Internet/O=Docker/OU=${APP_NAME}/CN=selfsigned"

COPY ./docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public_html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# configure timezone, mysql, apache
RUN cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
    echo "${TIMEZONE}" > /etc/timezone && \
    mkdir -p /run/apache2 && chown -R apache:apache /run/apache2 && chown -R apache:apache /var/www/localhost/htdocs/ && \
    sed -i 's#\#LoadModule rewrite_module modules\/mod_rewrite.so#LoadModule rewrite_module modules\/mod_rewrite.so#' /etc/apache2/httpd.conf && \
    sed -i 's#ServerName www.example.com:80#\nServerName localhost:80#' /etc/apache2/httpd.conf

RUN sed -i 's#display_errors = Off#display_errors = On#' /etc/php8.3/php.ini && \
    sed -i 's#upload_max_filesize = 2M#upload_max_filesize = 100M#' /etc/php8.3/php.ini && \
    sed -i 's#post_max_size = 8M#post_max_size = 100M#' /etc/php8.3/php.ini && \
    sed -i 's#session.cookie_httponly =#session.cookie_httponly = true#' /etc/php8.3/php.ini && \
    sed -i 's#error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT#error_reporting = E_ALL#' /etc/php8.3/php.ini


# 3. mod_rewrite for URL rewrite and mod_headers for .htaccess extra headers like Access-Control-Allow-Origin
RUN a2enmod rewrite headers socache_shmcb ssl

# 4. start with base php config, then add extensions
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

EXPOSE 80
EXPOSE 443

RUN docker-php-ext-install \
    gd \
    bz2 \
    intl \
    iconv \
    bcmath \
    opcache \
    calendar \
    pdo_mysql \
    zip

# 5. composer
COPY --from=composer:2.7.2 /usr/bin/composer /usr/bin/composer

# 6. we need a user with the same UID/GID with host user
# so when we execute CLI commands, all the host file's permissions and ownership remains intact
# otherwise command from inside container will create root-owned files and directories
ARG uid
RUN useradd -G www-data,root -u $uid -d /home/devuser devuser
RUN mkdir -p /home/devuser/.composer && \
    chown -R devuser:devuser /home/devuser

RUN DEFAULT_IGNORE_HTTPS_ERRORS=true

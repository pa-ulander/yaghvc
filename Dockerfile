FROM php:8.4-apache

RUN apt update

# 1. development packages
RUN apt update && apt install -y --no-install-recommends \
    apt-utils \
    g++ \
    git \
    zip \
    vim \
    curl \
    sudo \
    wget \
    unzip \
    sqlite3 \
    libnss3 \
    openssl \
    libgbm1 \
    libdrm2 \
    ssl-cert \
    libcups2 \
    apt-utils \
    libzip-dev \
    libicu-dev \
    libbz2-dev \
    libpng-dev \
    libxrandr2 \
    libxfixes3 \
    libgmp-dev \
    libasound2 \
    libwebp-dev \
    libatk1.0-0 \
    libjpeg-dev \
    libonig-dev \
    libxdamage1 \
    libldap2-dev \
    libxshmfence1 \
    libmcrypt-dev \
    libxkbcommon0 \
    libxcomposite1 \
    libsqlite3-dev \ 
    libpango-1.0-0 \
    ca-certificates \
    libreadline-dev \
    libfreetype6-dev \
    fonts-liberation \
    libatk-bridge2.0-0 \
    libpangocairo-1.0-0 \
    default-mysql-client \
    libjpeg62-turbo-dev && \
    docker-php-ext-configure gd --with-freetype=/usr/include/ --with-webp=/usr/include/ --with-jpeg=/usr/include/ && \
    wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb && \
    apt install -y ./google-chrome-stable_current_amd64.deb && \
    rm google-chrome-stable_current_amd64.deb && \
    apt-get autoremove -y && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# RUN pecl install ast

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
    pdo_mysql \
    pdo_sqlite

# 5. composer
COPY --from=composer:2.8.8 /usr/bin/composer /usr/bin/composer

RUN pecl install xdebug-3.4.2 \
    && docker-php-ext-enable xdebug

# 6. we need a user with the same UID/GID as the host user
# so when we execute CLI commands, all the host file's permissions and ownership remains intact
# otherwise command from inside container will create root-owned files and directories
ARG uid
ARG devuser
RUN useradd -G www-data,root -u $uid -d /home/$devuser $devuser
RUN mkdir -p /home/$devuser/.composer
RUN chown -R $devuser:$devuser /home/$devuser

# create PHPStan cache directory with proper permissions
RUN mkdir -p /tmp/phpstan/cache/PHPStan && \
    chown $devuser:www-data /tmp/phpstan/cache/PHPStan && \
    chmod 0775 /tmp/phpstan/cache/PHPStan

RUN DEFAULT_IGNORE_HTTPS_ERRORS=true

# 7. chrome binary env vars for Dusk browser testing
ENV CHROME_BIN=/usr/bin/chromium
ENV DUSK_CHROME_BIN=/usr/bin/chromium
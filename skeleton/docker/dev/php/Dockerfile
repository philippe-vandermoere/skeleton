FROM php:7.4-fpm-alpine

# php-fpm config
ENV PHP_FPM_PM_LOG_LEVEL=warning \
    PHP_FPM_PM_MAX_CHILDREN=5 \
    PHP_FPM_PM_START_SERVER=2 \
    PHP_FPM_PM_MIN_SPARE_SERVER=1 \
    PHP_FPM_PM_MAX_SPARE_SERVER=3 \
    PHP_FPM_PM_STATUS_PATH=/status \
    PHP_FPM_PM_PING_PATH=/ping \
    TIMEZONE=UTC

# Install requirement
RUN set -xe; \
    apk add --update --no-cache \
        bash \
        bash-completion \
        curl \
        openssl \
        git \
        make \
    ;

# Install php extension
RUN docker-php-ext-install -j$(nproc) \
        opcache \
        pdo \
        pdo_mysql \
    ;

# Install composer
RUN set -xe; \
    curl -sl https://getcomposer.org/composer.phar -o /usr/local/bin/composer; \
    chmod +x /usr/local/bin/composer;

ARG DOCKER_UID

RUN set -xe; \
    apk add --no-cache --virtual .build-deps shadow; \
    mkdir -p /var/www/html; \
    usermod -u ${DOCKER_UID} www-data -d /var/www; \
    chown -R www-data:www-data /var/www; \
    apk del --no-network .build-deps;

# Add bashrc
COPY config/.bashrc /var/www/.bashrc

# Configure php-fpm
COPY config/php-fpm/* /usr/local/etc/php-fpm.d/

# Configure php
COPY config/php/* /usr/local/etc/php/conf.d/

USER www-data

RUN composer global require hirak/prestissimo;

WORKDIR /var/www/html

CMD ["/bin/sh", "-c", "bin/console cache:warmup --env=dev && php-fpm"]

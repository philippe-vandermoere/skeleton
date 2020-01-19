FROM php:7.4-cli-alpine as source

# Install composer
RUN set -xe; \
    curl -sl https://getcomposer.org/composer-stable.phar -o /usr/local/bin/composer; \
    chmod +x /usr/local/bin/composer; \
    composer global require hirak/prestissimo;

RUN set -xe; \
    apk add --update --no-cache git; \
    git config --global user.email "philippe@wizacha.com"; \
    git config --global user.name "Philippe VANDERMOERE"

ARG BUILD_DATE
ARG VCS_REF

LABEL maintainer="Philippe VANDERMOERE <philippe@wizacha.com>" \
    org.label-schema.build-date=${BUILD_DATE} \
    org.label-schema.name="skeleton" \
    org.label-schema.vcs-ref=${VCS_REF} \
    org.label-schema.vcs-url="https://github.com/philippe-vandermoere/skeleton" \
    org.label-schema.schema-version="1.0.0"

COPY . /app

RUN set -xe; \
    cd /app; \
    composer install --no-dev --no-interaction --no-progress --ansi;

ENTRYPOINT ["/app/bin/console", "project:create", "--fix-files-owner"]

CMD ["-h"]

WORKDIR /project

FROM nginx:1.16-alpine

ENV PHP_FPM_UPSTREAM=php:9000 \
    DNS_RESOLVER=127.0.0.11

COPY config/vhost.conf /etc/nginx/conf.d/vhost.template
COPY config/nginx.conf /etc/nginx/nginx.conf

WORKDIR /var/www/html

CMD ["/bin/sh", "-c", "envsubst '$PHP_FPM_UPSTREAM,$DNS_RESOLVER' < /etc/nginx/conf.d/vhost.template > /etc/nginx/conf.d/default.conf && nginx -g 'daemon off;'"]

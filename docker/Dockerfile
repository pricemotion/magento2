FROM alpine:3.7

RUN apk add --no-cache \
    sudo \
    bash \
    shadow \
    php7-apache2 \
    php7-bcmath \
    php7-cli \
    php7-ctype \
    php7-curl \
    php7-dom \
    php7-gd \
    php7-iconv \
    php7-intl \
    php7-json \
    php7-mbstring \
    php7-openssl \
    php7-pdo_mysql \
    php7-simplexml \
    php7-soap \
    php7-xml \
    php7-xsl \
    php7-zip

COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT /entrypoint.sh

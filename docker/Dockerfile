FROM alpine:3.12

RUN apk add --no-cache \
    bash \
    gcc \
    libsodium-dev \
    make \
    musl-dev \
    shadow \
    sudo \
    php7-apache2 \
    php7-bcmath \
    php7-cli \
    php7-ctype \
    php7-curl \
    php7-dev \
    php7-dom \
    php7-fileinfo \
    php7-gd \
    php7-iconv \
    php7-intl \
    php7-json \
    php7-mbstring \
    php7-opcache \
    php7-openssl \
    php7-pdo_mysql \
    php7-pear \
    php7-phar \
    php7-session \
    php7-simplexml \
    php7-soap \
    php7-sockets \
    php7-tokenizer \
    php7-xml \
    php7-xmlwriter \
    php7-xsl \
    php7-zip

RUN pecl install libsodium
RUN echo 'extension=sodium.so' > /etc/php7/conf.d/00_sodium.ini

RUN rm -rf /var/www/localhost/htdocs && ln -sfn ../../../data/www/pub /var/www/localhost/htdocs

COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT /entrypoint.sh

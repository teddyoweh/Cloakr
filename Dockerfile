FROM php:8.1-cli

RUN apt-get update
RUN apt-get install -y git libzip-dev zip

RUN docker-php-ext-install zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /src
WORKDIR /src

# install the dependencies
RUN composer install -o --prefer-dist && chmod a+x cloakr

ENV port=8080
ENV domain=localhost
ENV username=username
ENV password=password
ENV cloakrConfigPath=/src/config/cloakr.php

COPY docker-entrypoint.sh /usr/bin/
RUN chmod 755 /usr/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]

FROM php:7.4-cli

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

CMD sed -i "s|username|${username}|g" ${cloakrConfigPath} && sed -i "s|password|${password}|g" ${cloakrConfigPath} && php cloakr serve ${domain} --port ${port} --validateAuthTokens

# get input arguments
ARG  PHP_VERSION=7.4

# first build composer latest
FROM composer:latest

# then expected php version
FROM php:${PHP_VERSION}-cli-alpine

# copy built composer binary
COPY --from=composer /usr/bin/composer /usr/bin/composer

# install git
RUN apk update && apk upgrade && apk add --no-cache git

# configure/install required PHP extensions
ADD https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/master/install-php-extensions /usr/local/bin/
RUN chmod uga+x /usr/local/bin/install-php-extensions && sync && install-php-extensions bcmath zip xdebug

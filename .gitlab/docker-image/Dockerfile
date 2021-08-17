FROM php:8.0

COPY --from=composer /usr/bin/composer /usr/bin/composer

# install git because it is used by composer
RUN apt-get update -yqq
RUN apt-get install git zip -yqq

# install php extension
RUN pecl install pcov
RUN docker-php-ext-enable pcov
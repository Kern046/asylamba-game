FROM php:8.1-fpm-alpine


# Install SUPERVISOR and dependencies
RUN apk update

RUN apk add rabbitmq-c-dev icu-dev

# Install PHP etxensions
RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
	&& docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql intl opcache \
    && pecl install amqp redis \
    && docker-php-ext-enable amqp pdo_mysql redis \
    && apk del pcre-dev ${PHPIZE_DEPS}

RUN curl -s https://getcomposer.org/installer | php
RUN alias composer="php composer.phar"

COPY docker-entrypoint.sh /entrypoint.sh

RUN chmod a+x /entrypoint.sh

WORKDIR /srv/app

ENTRYPOINT ["/entrypoint.sh"]

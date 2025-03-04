FROM php:8.4-cli-alpine

# Install SUPERVISOR and dependencies
RUN apk update

RUN apk add supervisor wget rabbitmq-c-dev icu-dev

# Install PHP etxensions
RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
	&& docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql intl opcache \
    && pecl install amqp redis \
    && docker-php-ext-enable amqp pdo_mysql redis \
    && apk del pcre-dev ${PHPIZE_DEPS}

WORKDIR /srv/app

CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]

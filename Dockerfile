FROM moodlehq/moodle-php-apache:8.3-bookworm

ARG MOODLE_BRANCH=MOODLE_501_STABLE

ENV COMPOSER_ALLOW_SUPERUSER=1

USER root

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN php -m | grep -i '^sodium$'

WORKDIR /var/www/html

RUN rm -rf /var/www/html/* \
    && git clone --branch ${MOODLE_BRANCH} --depth 1 https://github.com/moodle/moodle.git /var/www/html

RUN composer install --no-dev --classmap-authoritative --no-cache

RUN mkdir -p /var/www/moodledata \
    && chown -R www-data:www-data /var/www/moodledata \
    && chmod -R 775 /var/www/moodledata \
    && chown -R www-data:www-data /var/www/html

COPY php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 80

# We intentionally keep the container user as root here.
# Apache starts as root in order to bind to port 80, then drops privileges
# to the web server user defined by the base image, typically www-data.
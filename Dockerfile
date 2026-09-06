FROM moodlehq/moodle-php-apache:8.3-bookworm

# Version Moodle FIGEE sur un tag git precis (pas une branche mouvante).
ARG MOODLE_BRANCH=v5.1.6

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

# -----------------------------------------------------------------------------
# PLUGINS TIERS
#
# Installés ici, à la construction de l'image, et non depuis l'interface web.
# Trois raisons : l'installation est reproductible à l'identique chez tous les
# collègues, elle survit à toute reconstruction de l'image, et la version est
# figée sur une branche précise plutôt que de suivre une branche mouvante.
#
# Chaque plugin est cloné sans historique, puis son dossier .git est supprimé :
# il ne doit pas devenir un dépôt imbriqué dans le nôtre.
#
# Après ajout ou changement de version, il faut reconstruire l'image puis
# lancer la mise à jour Moodle, qui crée les tables du plugin :
#   docker compose build
#   docker compose up -d
#   docker compose exec -u www-data moodle php /var/www/html/admin/cli/upgrade.php --non-interactive
# -----------------------------------------------------------------------------

# Attendance : feuilles de présence par séance, export, auto-déclaration.
ARG ATTENDANCE_BRANCH=MOODLE_501_STABLE

RUN git clone --branch ${ATTENDANCE_BRANCH} --depth 1 \
        https://github.com/danmarsden/moodle-mod_attendance.git \
        /var/www/html/public/mod/attendance \
    && rm -rf /var/www/html/public/mod/attendance/.git

# Custom certificate : attestations de suivi personnalisables.
#
# Branche 5.0 volontairement, alors que nous sommes en 5.1 : ce plugin n'a pas
# publié de branche 5.1. Ce n'est pas gênant, car `$plugin->requires` déclare
# une version MINIMALE de Moodle. Une branche 5.0 s'installe donc sur 5.1.
# C'est l'inverse qui échouerait : MOODLE_502_STABLE exigerait Moodle 5.2.
ARG CUSTOMCERT_BRANCH=MOODLE_500_STABLE

RUN git clone --branch ${CUSTOMCERT_BRANCH} --depth 1 \
        https://github.com/mdjnelson/moodle-mod_customcert.git \
        /var/www/html/public/mod/customcert \
    && rm -rf /var/www/html/public/mod/customcert/.git

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
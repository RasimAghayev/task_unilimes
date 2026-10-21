# The application image of csv-import-api.
#
# The base image is pinned by digest: the tag would let the runtime drift
# away from the version the code was written for. The application source is
# copied into the image, so the container needs no bind mount of the
# repository. Every bind mount of the compose stack lives under var/ (R92).

FROM php:8.5-apache@sha256:73a1c4a912f1ba807073d47a3b875fbf36ff88b563cc2c73b16da2387a5694ca AS base

# The application talks to the database with mysqli. The extension is part of
# the image, so nothing has to be installed on the host.
RUN docker-php-ext-install mysqli

WORKDIR /var/www/html

# The test image carries the suite next to the application. The suite needs
# no database and no network, so it runs on its own. The layout of the image
# is the layout of the repository, so the paths of the suite hold in both
# places.
FROM base AS test

COPY app/ /var/www/html/app/
COPY tests/ /var/www/html/tests/

ENTRYPOINT ["php", "/var/www/html/tests/run-tests.php"]

# The runtime image carries the application only. The process runs as the
# unprivileged www-data user, and the image holds no secret.
FROM base AS runtime

COPY --chown=www-data:www-data build/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY --chown=www-data:www-data app/ /var/www/html/

USER www-data

# The web server answers on port 80. The check opens the port, which tells a
# running server from a dead one without pulling another tool into the image.
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=10s \
  CMD php -r "exit(@fsockopen('127.0.0.1', 80) ? 0 : 1);"

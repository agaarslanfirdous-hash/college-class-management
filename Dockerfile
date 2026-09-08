# Render has no native PHP runtime — deploy this image as a Docker web service.
FROM php:8.3-cli-bookworm

RUN docker-php-ext-install pdo_mysql

WORKDIR /var/www/html

COPY app ./app
COPY public_html ./public_html
COPY database ./database
COPY storage ./storage
COPY scripts ./scripts

RUN mkdir -p storage/logs storage/backups public_html/uploads \
    && chmod -R 775 storage public_html/uploads

ENV PORT=10000
EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public_html public_html/router.php"]

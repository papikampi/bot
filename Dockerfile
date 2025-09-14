FROM php:8.1-apache

RUN apt-get update && apt-get install -y     libxml2-dev     curl     unzip     && docker-php-ext-install xml

WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html     && chmod -R 755 /var/www/html     && mkdir -p /var/www/html/storage && chown -R www-data:www-data /var/www/html/storage

EXPOSE 80
CMD ["apache2-foreground"]

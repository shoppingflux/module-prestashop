FROM 202ecommerce/prestashop:1.7.8.5

RUN rm -Rf var/www/html/modules/shoppingfeed/

RUN pecl install xdebug
RUN doc ker-php-ext-enable xdebug
RUN mkdir -p /var/www/html/202/cover/

WORKDIR /var/www/html/modules/shoppingfeed/

ENTRYPOINT ["sh", "202/docker/entrypoint.sh"]
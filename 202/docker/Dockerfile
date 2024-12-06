FROM 202ecommerce/prestashop:8.1.5

RUN rm -Rf var/www/html/modules/shoppingfeed/

WORKDIR /var/www/html/modules/shoppingfeed/

ENTRYPOINT ["sh", "202/docker/entrypoint.sh"]
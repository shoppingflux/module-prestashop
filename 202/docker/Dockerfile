FROM 202ecommerce/prestashop:1.7.8.3

ARG RUN_USER

RUN if [ "${RUN_USER}" ]; then useradd -m $RUN_USER; else RUN_USER=www-data; fi

RUN echo "export APACHE_RUN_USER=$RUN_USER \
export APACHE_RUN_GROUP=$RUN_USER" >> /etc/apache2/envvars 

RUN chown $RUN_USER:$RUN_USER /var/www/html -Rf

COPY 202/docker/entrypoint.sh /tmp/

WORKDIR /var/www/html/modules/shoppingfeed

ENTRYPOINT ["sh", "202/docker/entrypoint.sh"]

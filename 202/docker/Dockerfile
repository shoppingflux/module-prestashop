FROM prestashop/prestashop:1.7.8.3

ARG RUN_USER

RUN useradd -m $RUN_USER

RUN apt-get update \
	&& apt-get install -y mariadb-server && /etc/init.d/mariadb start
	
RUN /etc/init.d/mariadb start && mysqladmin --user=root password "password"

RUN /etc/init.d/mariadb start && php install/index_cli.php --language=en --country=fr --domain=localhost --db_server=127.0.0.1 --db_user=root --db_name=prestashop --db_password=password --firstname=Foo --lastname=Bar --email=demo@202-ecommerce.com --password=demodemo --db_create=1

RUN rm -Rf /var/www/html/install

RUN mv /var/www/html/admin /var/www/html/admin202

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
		&& php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
		&& php composer-setup.php \
		&& php -r "unlink('composer-setup.php');" \
		&& mv composer.phar /usr/bin/composer

RUN echo "export APACHE_RUN_USER=$RUN_USER \
export APACHE_RUN_GROUP=$RUN_USER" >> /etc/apache2/envvars 

RUN chown $RUN_USER:$RUN_USER /var/www/html -Rf

COPY 202/docker/entrypoint.sh /tmp/

WORKDIR /var/www/html/modules/shoppingfeed

ENTRYPOINT ["sh", "202/docker/entrypoint.sh"]

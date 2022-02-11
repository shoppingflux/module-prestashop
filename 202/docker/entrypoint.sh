#!/bin/bash

set -e
set -x

/etc/init.d/mariadb start

if [ "$PS_DOMAIN" ]; then 
    mysql -h localhost -u root -ppassword prestashop -e "UPDATE ps_shop_url SET domain='$PS_DOMAIN', domain_ssl='$PS_DOMAIN'"
fi

cd  /var/www/html/modules/shoppingfeed

composer update

php /var/www/html/bin/console prestashop:module install shoppingfeed -e prod

mysql -h localhost -u root -ppassword prestashop -e "
INSERT IGNORE INTO ps_shoppingfeed_token (id_shoppingfeed_token, id_shop, id_lang, id_currency, content, active, date_add, date_upd) VALUES
(1, 1, 1, 1, 'token-shoppingfeed-api', 1, NOW(), NOW());
"

if [ ${RUN_USER} ]; then
    chown $RUN_USER:$RUN_USER /var/www/html -Rf
else
    chown www-data:www-data /var/www/html -Rf
fi

exec apache2-foreground

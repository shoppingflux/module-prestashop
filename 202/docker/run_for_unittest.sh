#!/bin/bash

set -e
set -x

/etc/init.d/mariadb start

php /var/www/html/bin/console prestashop:module install shoppingfeed -e prod

chown www-data:www-data /var/www/html/var -Rf

mysql -h localhost -u root prestashop -e "
INSERT IGNORE INTO ps_shoppingfeed_token (id_shoppingfeed_token, id_shop, id_lang, id_currency, content, active, date_add, date_upd) VALUES
(1, 1, 1, 1, 'token-shoppingfeed-api', 1, NOW(), NOW());
"
cd /var/www/html/modules/shoppingfeed/
vendor/bin/phpunit -c 202/phpunit.xml
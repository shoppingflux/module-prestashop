#!/bin/bash

set -e
set -x

/etc/init.d/mariadb start

php /var/www/html/bin/console prestashop:module install shoppingfeed -e prod

chown www-data:www-data /var/www/html/var -Rf

echo "Add data fixtures for Unit Tests"

mysql -h localhost -u root prestashop -e "
TRUNCATE ps_shoppingfeed_order;
UPDATE ps_configuration SET value = '[\"5\",\"4\"]' WHERE name = 'SHOPPINGFEED_SHIPPED_ORDERS';
UPDATE ps_configuration SET value = '[\"6\"]' WHERE name = 'SHOPPINGFEED_CANCELLED_ORDERS';
UPDATE ps_configuration SET value = '[\"7\"]' WHERE name = 'SHOPPINGFEED_REFUNDED_ORDERS';
INSERT IGNORE INTO ps_shoppingfeed_token (id_shoppingfeed_token, id_shop, id_lang, id_currency, content, active, date_add, date_upd) VALUES
(1, 1, 1, 1, 'token-shoppingfeed-api', 1, NOW(), NOW());
INSERT IGNORE INTO ps_shoppingfeed_carrier (id_shoppingfeed_carrier, name_marketplace, name_carrier, id_carrier_reference, is_new, date_add, date_upd) VALUES
(1,	'Amazon',	'Colissimo',	1,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(2,	'NatureEtDecouvertes',	'Point Relais (Chrono Relais)',	2,	0,	'2022-02-17 07:44:26',	'2022-02-17 00:00:00');
UPDATE ps_product_lang SET available_now = 'disponible', available_later = 'non disponible' WHERE id_product = 1;
UPDATE ps_product_lang SET available_now = 'disponible', available_later = 'non disponible' WHERE id_product = 3;
UPDATE ps_stock_available SET quantity = 0 WHERE id_product = 3 and id_product_attribute = 13;
UPDATE ps_stock_available SET quantity = 0 WHERE id_product = 4 and id_product_attribute = 16;
"


cd /var/www/html/modules/shoppingfeed/

vendor/bin/phpunit -c 202/phpunit.xml

#!/bin/bash

set -e
set -x

/etc/init.d/mariadb start

php /var/www/html/bin/console prestashop:module install shoppingfeed -e prod
php /var/www/html/bin/console prestashop:module install dpdfrance -e prod
php /var/www/html/bin/console prestashop:module install colissimo -e prod

echo "Add data fixtures for Unit Tests"

mysql -h localhost -u root prestashop -e "
UPDATE ps_product_attribute SET reference = 'demo_17_white' WHERE id_product = 11 AND default_on = 1;

TRUNCATE ps_shoppingfeed_order;
UPDATE ps_configuration SET value = '[\"5\",\"4\"]' WHERE name = 'SHOPPINGFEED_SHIPPED_ORDERS';
UPDATE ps_configuration SET value = '[\"6\"]' WHERE name = 'SHOPPINGFEED_CANCELLED_ORDERS';
UPDATE ps_configuration SET value = '[\"7\"]' WHERE name = 'SHOPPINGFEED_REFUNDED_ORDERS';
UPDATE ps_configuration SET value = '{\"ShoppingfeedAddon\\OrderImport\\Rules\\AmazonEbay\":{\"enabled\":\"1\"},\"ShoppingfeedAddon\\OrderImport\\Rules\\ChangeStateOrder\":{\"end_order_state\":\"\"},\"ShoppingfeedAddon\\OrderImport\\Rules\\ShippedByMarketplace\":{\"end_order_state_shipped\":\"5\"},\"ShoppingfeedAddon\\OrderImport\\Rules\\SymbolConformity\":{\"enabled\":\"1\"}}' WHERE name = 'SHOPPINGFEED_ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION';

INSERT IGNORE INTO ps_shoppingfeed_token (id_shoppingfeed_token, id_shop, id_lang, id_currency, content, active, date_add, date_upd) VALUES
(1, 1, 1, 1, 'token-shoppingfeed-api', 1, NOW(), NOW());

REPLACE INTO ps_shoppingfeed_carrier (id_shoppingfeed_carrier, name_marketplace, name_carrier, id_carrier_reference, is_new, date_add, date_upd) VALUES
(1,	'Amazon',	'Colissimo',	1,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(2,	'NatureEtDecouvertes',	'Point Relais (Chrono Relais)',	2,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(3,	'Colizey', 'Colissimo Relais',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(4,	'CDiscount', 'SO1',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00');

UPDATE ps_product_lang SET available_now = 'disponible', available_later = 'non disponible' WHERE id_product = 1;
UPDATE ps_product_lang SET available_now = 'disponible', available_later = 'non disponible' WHERE id_product = 3;
UPDATE ps_stock_available SET quantity = 0 WHERE id_product = 3 and id_product_attribute = 13;
UPDATE ps_stock_available SET quantity = 0 WHERE id_product = 4 and id_product_attribute = 16;

TRUNCATE ps_colissimo_pickup_point;
"

cd /var/www/html/modules/shoppingfeed/

php -dmemory_limit=512M vendor/bin/phpunit -c 202/phpunit.xml

chown www-data:www-data /var/www/html/var -Rf
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

INSERT IGNORE INTO ps_tax (id_tax, rate, active, deleted) VALUES
(40, 0.190, 1, 0);
INSERT IGNORE INTO ps_tax_lang (id_tax, id_lang, name) VALUES
(40, 1, 'TPH'), (40, 2, 'TPH');
INSERT IGNORE INTO ps_tax_rules_group (id_tax_rules_group, name, active, deleted, date_add, date_upd) VALUES
(10, 'TVA + TPH', 1, 0, NOW(), NOW());
INSERT IGNORE INTO ps_tax_rules_group_shop (id_tax_rules_group, id_shop) VALUES (10, 1);
INSERT IGNORE INTO ps_tax_rule (id_tax_rules_group, id_country, id_state, zipcode_from, zipcode_to, id_tax, behavior) VALUES
(10, 8, 0, 0, 0, 40, 2),
(10, 8, 0, 0, 0, 1, 2);
UPDATE ps_product SET id_tax_rules_group = 10 WHERE id_product = 18;
UPDATE ps_product_shop SET id_tax_rules_group = 10 WHERE id_product = 18;

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

php -dmemory_limit=512M -dxdebug.mode=coverage vendor/bin/phpunit -c 202/phpunit.xml

chown www-data:www-data /var/www/html/var -Rf
#!/bin/bash

set -e
set -x

/etc/init.d/mariadb start

sleep 3
mysql -h localhost -u root prestashop -e "
SET GLOBAL wait_timeout=6000;
SET GLOBAL interactive_timeout=6000;
SET GLOBAL max_allowed_packet=1073741824;
"

php /var/www/html/bin/console prestashop:module install shoppingfeed -e prod
rm /var/www/html/var/cache/*/* -Rf
php /var/www/html/bin/console prestashop:module install dpdfrance -e prod
rm /var/www/html/var/cache/*/* -Rf
php /var/www/html/bin/console prestashop:module install colissimo -e prod
rm /var/www/html/var/cache/*/* -Rf
php /var/www/html/bin/console prestashop:module install mondialrelay -e prod
rm /var/www/html/var/cache/*/* -Rf
php /var/www/html/bin/console prestashop:module install nkmgls -e prod
rm /var/www/html/var/cache/*/* -Rf

echo "Add data fixtures for Unit Tests"

mysql -h localhost -u root prestashop -e "
TRUNCATE ps_shoppingfeed_preloading;
TRUNCATE ps_mondialrelay_carrier_method;
INSERT INTO ps_mondialrelay_carrier_method (id_carrier, delivery_mode, insurance_level, is_deleted, id_reference, date_add, date_upd)
VALUES ('1', 'HOM', '0', '0', '1', '2022-06-23 11:30:14', '2022-06-23 11:30:14');

DELETE FROM ps_configuration WHERE name LIKE 'MONDIALRELAY_%';
INSERT INTO ps_configuration (id_shop_group, id_shop, name, value, date_add, date_upd) VALUES
(NULL, NULL, 'MONDIALRELAY_WEBSERVICE_ENSEIGNE', 'BDTEST13', now(), now()),
(NULL, NULL, 'MONDIALRELAY_WEBSERVICE_BRAND_CODE', '11', now(), now()),
(NULL, NULL, 'MONDIALRELAY_WEBSERVICE_KEY', 'PrivateK', now(), now()),
(NULL, NULL, 'MONDIALRELAY_LABEL_LANG', 'FR', now(), now()),
(NULL, NULL, 'MONDIALRELAY_WEIGHT_COEFF', '1', now(), now()),
(NULL, NULL, 'SHOPPINGFEED_ORDER_SHIPPED_BY_MARKETPLACE_IMPORT_PERMANENT_SINCE_DATE', '2022-01-01', now(), now());

UPDATE ps_product_attribute SET reference = 'demo_17_white' WHERE id_product = 11 AND default_on = 1;
UPDATE ps_product_attribute_shop SET ecotax = 6 WHERE id_product = 5 AND id_product_attribute = 19;;
"

mysql -h localhost -u root prestashop -e "
TRUNCATE ps_shoppingfeed_order;
TRUNCATE ps_shoppingfeed_task_order;
UPDATE ps_configuration SET value = '[\"5\",\"4\"]' WHERE name = 'SHOPPINGFEED_SHIPPED_ORDERS';
UPDATE ps_configuration SET value = '[\"6\"]' WHERE name = 'SHOPPINGFEED_CANCELLED_ORDERS';
UPDATE ps_configuration SET value = '[\"7\"]' WHERE name = 'SHOPPINGFEED_REFUNDED_ORDERS';
UPDATE ps_configuration SET value = '{\"ShoppingfeedAddon\\\\\\\\OrderImport\\\\\\\\Rules\\\\\\\\TaxForBusiness\":{\"enabled\":\"1\"},\"ShoppingfeedAddon\\\\\\\\OrderImport\\\\\\\\Rules\\\\\\\\AmazonEbay\":{\"enabled\":\"1\"},\"ShoppingfeedAddon\\\\\\\\OrderImport\\\\\\\\Rules\\\\\\\\ChangeStateOrder\":{\"end_order_state\":\"\"},\"ShoppingfeedAddon\\\\\\\\OrderImport\\\\\\\\Rules\\\\\\\\ShippedByMarketplace\":{\"end_order_state_shipped\":\"5\"},\"ShoppingfeedAddon\\\\\\\\OrderImport\\\\\\\\Rules\\\\\\\\SymbolConformity\":{\"enabled\":\"1\"}}' WHERE name = 'SHOPPINGFEED_ORDER_IMPORT_SPECIFIC_RULES_CONFIGURATION';
UPDATE ps_configuration SET value = '1' WHERE name = 'PS_CART_RULE_FEATURE_ACTIVE';
UPDATE ps_configuration SET value = '1' WHERE name = 'SHOPPINGFEED_ORDER_IMPORT_SHIPPED_MARKETPLACE';
UPDATE ps_configuration SET value = '0' WHERE name = 'SHOPPINGFEED_ORDER_STATUS_TIME_SHIFT';

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
"
mysql -h localhost -u root prestashop -e "
REPLACE INTO ps_shoppingfeed_carrier (id_shoppingfeed_carrier, name_marketplace, name_carrier, id_carrier_reference, is_new, date_add, date_upd) VALUES
(1,	'Amazon',	'Colissimo',	1,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(2,	'NatureEtDecouvertes',	'Point Relais (Chrono Relais)',	2,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(3,	'Colizey', 'Colissimo Relais',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(4,	'CDiscount', 'SO1',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(5,	'Manomano', 'Colissimo',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(6,	'showroomprive', 'Colissimo',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(7,	'Veepeegroup', 'Colissimo',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(8,	'bhv', 'Colissimo',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(9,	'GaleriesLafayette', 'Colissimo',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(10,	'leroymerlin', 'Colissimo',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(11,	'zalandomyunittest', 'Colissimo',	7,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00');

UPDATE ps_product_lang SET available_now = 'disponible', available_later = 'non disponible' WHERE id_product = 1;
UPDATE ps_product_lang SET available_now = 'disponible', available_later = 'non disponible' WHERE id_product = 3;
UPDATE ps_stock_available SET quantity = 0 WHERE id_product = 3 and id_product_attribute = 13;
UPDATE ps_stock_available SET quantity = 0 WHERE id_product = 4 and id_product_attribute = 16;

DELETE FROM ps_product_carrier WHERE id_product IN (1,8);
REPLACE INTO ps_product_carrier (id_product, id_carrier_reference, id_shop) VALUES
(1,1,1), (8,2,1);
REPLACE INTO ps_carrier_zone (id_carrier, id_zone) VALUES
(1,9), (2,9);

TRUNCATE ps_cart_rule;
REPLACE INTO ps_cart_rule (id_cart_rule ,id_customer,date_from,date_to,description,quantity,quantity_per_user,priority,partial_use,code,minimum_amount,minimum_amount_tax,minimum_amount_currency,minimum_amount_shipping,country_restriction,carrier_restriction,group_restriction,cart_rule_restriction,product_restriction,shop_restriction,free_shipping,reduction_percent,reduction_amount,reduction_tax,reduction_currency,reduction_product,reduction_exclude_special,gift_product,gift_product_attribute,highlight,active,date_add,date_upd)
    VALUES (1, 0,'2022-02-17 00:00:00','2042-02-17 00:00:00','',999,999,1,1,'',0.000000,0,1,0,0,0,0,0,0,0,0,0.00,0.000000,0,1,0,0,7,0,0,1,'2022-02-17 00:00:00','2022-02-17 00:00:00');
REPLACE INTO ps_cart_rule_lang (id_cart_rule,id_lang,name)
    VALUES (1,1,'gift');
"
mysql -h localhost -u root prestashop -e "
TRUNCATE ps_colissimo_pickup_point;

INSERT IGNORE INTO ps_feature (id_feature,  position)
VALUES (3, 2);
INSERT IGNORE INTO ps_feature_shop (id_feature,  id_shop)
VALUES (3, 1);
INSERT IGNORE INTO ps_feature_lang (id_feature, id_lang, name)
VALUES (3, 1, 'Logiciel PC'), (3, 2, 'Logiciel PC');
INSERT IGNORE INTO ps_feature_product (id_feature, id_product, id_feature_value)
VALUES
(2, 18, 3),
(2, 18, 1),
(3, 18, 3),
(3, 18, 1);
"

cd /var/www/html/modules/shoppingfeed/

php -dmemory_limit=512M vendor/bin/phpunit -c 202/phpunit.xml

chown www-data:www-data /var/www/html/var -Rf

#!/bin/bash

set -e
set -x

if [ "${RUN_USER}" != "www-data" ]; then 
useradd -m $RUN_USER; 
echo "export APACHE_RUN_USER=$RUN_USER \
export APACHE_RUN_GROUP=$RUN_USER" >> /etc/apache2/envvars 
fi

/etc/init.d/mariadb start

if [ "$PS_DOMAIN" ]; then 
    mysql -h localhost -u root prestashop -e "UPDATE ps_shop_url SET domain='$PS_DOMAIN', domain_ssl='$PS_DOMAIN'"
fi

cd  /var/www/html/modules/shoppingfeed

composer update

php /var/www/html/bin/console prestashop:module install shoppingfeed -e prod

mysql -h localhost -u root prestashop -e "
UPDATE ps_configuration SET value = '[\"5\",\"4\"]' WHERE name = 'SHOPPINGFEED_SHIPPED_ORDERS';
UPDATE ps_configuration SET value = '[\"6\"]' WHERE name = 'SHOPPINGFEED_CANCELLED_ORDERS';
UPDATE ps_configuration SET value = '[\"7\"]' WHERE name = 'SHOPPINGFEED_REFUNDED_ORDERS';
INSERT IGNORE INTO ps_shoppingfeed_token (id_shoppingfeed_token, id_shop, id_lang, id_currency, content, active, date_add, date_upd) VALUES
(1, 1, 1, 1, 'token-shoppingfeed-api', 1, NOW(), NOW());
INSERT IGNORE INTO ps_shoppingfeed_carrier (id_shoppingfeed_carrier, name_marketplace, name_carrier, id_carrier_reference, is_new, date_add, date_upd) VALUES
(1,	'Amazon',	'Colissimo',	1,	0,	'2022-02-17 00:00:00',	'2022-02-17 00:00:00'),
(2,	'NatureEtDecouvertes',	'Point Relais (Chrono Relais)',	2,	0,	'2022-02-17 07:44:26',	'2022-02-17 00:00:00');
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
"

chown $RUN_USER:$RUN_USER /var/www/html -Rf

exec apache2-foreground
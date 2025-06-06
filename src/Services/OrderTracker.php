<?php

/**
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace ShoppingfeedAddon\Services;

use ShoppingFeed\Sdk\Client\ClientOptions;
use ShoppingfeedClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use ShoppingfeedClasslib\Utils\Translate\TranslateTrait;
use ShoppingfeedPrefix\GuzzleHttp\Client;

class OrderTracker
{
    use TranslateTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Db::getInstance();
    }

    public function track(\Order $order)
    {
        $sft = new \ShoppingfeedToken();
        $tokens = $sft->findAllActive();
        $clientOptions = new ClientOptions();
        $client = new Client([
            'base_uri' => 'https://tag.shopping-flux.com',
            'headers' => $clientOptions->getHeaders(),
        ]);

        ProcessLoggerHandler::openLogger();
        foreach ($tokens as $token) {
            $endpoint = sprintf(
                'order/%s?ip=%s',
                base64_encode(implode('|', [$token['shoppingfeed_store_id'], $order->id, $order->total_paid])),
                $this->getIP($order)
            );
            $client->request(
                'GET',
                $endpoint
            );
            ProcessLoggerHandler::addLog(
                $this->l('Sending the order tracking info for token', 'OrderTracker') . $token['content'],
                'Order',
                $order->id
            );
        }
        ProcessLoggerHandler::closeLogger();
    }

    protected function getIP(\Order $order)
    {
        // Search IP in the table connections
        $query = (new \DbQuery())
            ->from('guest', 'g')
            ->innerJoin('connections', 'c', 'g.`id_guest` = c.`id_guest` AND g.`id_customer` = ' . (int) $order->id_customer)
            ->orderBy('c.`date_add` DESC')
            ->select('c.`ip_address`');

        if ($ip = $this->db->getValue($query)) {
            $ip = long2ip($ip);
        }

        if ($ip) {
            return $ip;
        }

        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            // Cloudflare is directly providing the client IP in this server variable (when correctly set)
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // We retrieve the proxy list
            $ipForwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'];
            // In case of multiple proxy, there values will be split by comma. It will list each server IP the request passed throug
            $proxyList = explode(',', $ipForwardedFor);
            // The first IP of the list is the client IP (the last IP is the last proxy)
            $ip = trim(reset($proxyList));
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}

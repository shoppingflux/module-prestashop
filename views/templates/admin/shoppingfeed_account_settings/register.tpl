{*
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from SARL 202 ecommence
* Use, copy, modification or distribution of this source file without written
* license agreement from the SARL 202 ecommence is strictly forbidden.
* In order to obtain a license, please contact us: tech@202-ecommerce.com
* ...........................................................................
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe 202 ecommence
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommence est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
* ...........................................................................
*
* @author    202-ecommerce <tech@202-ecommerce.com>
* @copyright Copyright (c) 202-ecommerce
* @license   Commercial license
*}

{extends file="helpers/form/form.tpl"}

{block name="legend" append}
   <div class="form-group welcome_shoppingfeed">
       <div class="col-lg-6 logo_shoppingfeed">
           <img class="mb-0" id="" src="{$img_path|escape:'htmlall':'UTF-8'}ad.png"/>
       </div>
       <div class="col-lg-6 desc_shoppingfeed">
        <h2><b>{l s='Shopping Feed exports your products to the largest marketplaces in the world, all from a single intuitive platform.' mod='shoppingfeed'}</b>
        {l s='Through our free setup and expert support, we help thousands of storefronts increase their sales and visibility.' mod='shoppingfeed'}</h2>
           <p class="text-center">
        <a class="btn btn-success btn-lg" target="_blank" href="https://register.shopping-feed.com/sign/prestashop?phone={$phone|urlencode}&email={$email|urlencode}&feed={$link->getModuleLink('shoppingfeed', 'product')|escape:'html'|urlencode}?token={$shoppingfeed_token|urlencode}&lang={$lang|urlencode}" value="{l s='Send' mod='shoppingfeed'}">{l s='Register Now!' mod='shoppingfeed'}</a>
           </p>
        <br/>
        <b>{l s='Put your feeds to work:' mod='shoppingfeed'} </b>{l s='A single platform to manage your products and sales on the world\'s marketplaces.' mod='shoppingfeed'}<br />
        <b>{l s='Set it and forget it:' mod='shoppingfeed'} </b>{l s='Automated order processes for each marketplace channel you sell on, quadrupling your revenue, and not your workload.' mod='shoppingfeed'}<br />
        <b>{l s='Try Before You Buy:' mod='shoppingfeed'} </b> {l s='Expert Channel Setup is always free on Shopping Feed, giving you risk-free access to your brand new channel before becoming a member.' mod='shoppingfeed'} <br />
        <br/>
        <ol style="line-height:2em; list-style-type:circle; padding: 0 0 0 20px">
        <li>{l s='Optimize your channels, and calculate realtime Return On Investment for all the leading comparison shopping engines like Google Shopping, Ratuken, shopping.com, NextTag, ShopZilla and more.' mod='shoppingfeed'}</li>
        <li>{l s='Connect your storefront to all the major marketplaces like eBay, Amazon, Sears and 11 Main, while managing your pricing, inventory, and merchandising through a single, intuitive platform.' mod='shoppingfeed'}</li>
        <li>{l s='Prepare for an evolving ecosystem: New features, tools, and integrations are being created every month, at no extra cost.' mod='shoppingfeed'}</li>
        <li>{l s='Be seen: With over 50 different marketplaces and shopping engines under one roof, Shopping Feed helps you find your right audience.' mod='shoppingfeed'}</li>
        </ol><br/>
        {l s='With over 1400 Members worldwide, helping them achieve over $13 Million in monthly revenue,'}<b>
        {l s='Lets us help you put your feeds to work.' mod='shoppingfeed'}</b>
       </div>
   </div>
{/block}

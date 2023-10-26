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
           <img class="mb-0" id="" src="{$img_path|escape:'htmlall':'UTF-8'}picture_prestashop_module.png"/>
       </div>
       <div class="col-lg-6 desc_shoppingfeed">
            {l s='With Shoppingfeed, you can finally export your products to your favorite marketing channels and marketplaces.' mod='shoppingfeed'}  <br/>
            {l s='With our guidance included from setup and our expert support, we help thousands of brands, retailers, and pure-players increase their sales.' mod='shoppingfeed'}  <br/>
            <br/>
            {l s='Simplify your daily routine: a single platform to manage the distribution of your products worldwide.' mod='shoppingfeed'}  <br/>
            <br/>
            {l s='Set up in just a few clicks!' mod='shoppingfeed'}  <br/>
            {l s='Adjust your prices, modify your data with our search/replace tool.' mod='shoppingfeed'}  <br/>
            <br/>
            {l s='So why not you? Take the plunge now.' mod='shoppingfeed'}  <br/>
            {l s='More than 2500 merchants connect to Shoppingfeed every day to boost their sales.' mod='shoppingfeed'}  <br/>
            <br/>
            {l s='Export your offerings to the most reputable marketplaces: Amazon, Leboncoin, La Redoute, Cdiscount, and dozens more.' mod='shoppingfeed'}  <br/>
            {l s='Over 200 marketplaces are connected to Shoppingfeed! The complete and updated list is available on our website.' mod='shoppingfeed'}  <br/>
            <br/>
            {l s='Shoppingfeed will automatically synchronize your orders within your PrestaShop environment and keep all your stock and prices updated.' mod='shoppingfeed'}  <br/>
            <br/>
            {l s='We are constantly innovating. Get ready for new features and tools every month, at no extra cost.' mod='shoppingfeed'}  <br/>
            <br/>
       </div>
   </div>
{/block}

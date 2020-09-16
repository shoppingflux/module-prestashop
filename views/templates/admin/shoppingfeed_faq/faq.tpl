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
    <div class="form-group">
        <div id="accordion">
            <div class="card">
                <div class="card-header" id="headingOne">
                    <h5 class="mb-0">
                        <a class="btn btn-link collapsed" role="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                           <span>{l s='Does the Shoppingfeed Prestashop Plugin (Feed&Order) module work with the official Shopping Feed module?' mod='shoppingfeed'}
                           </span>
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </h5>
                </div>

                <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                    <div class="card-body">
                        {l s='No. This new module replace the old one. All features need are now in this module: your product feed, update stock or price in 15 minutes or import your orders from marketplaces.' mod='shoppingfeed'}
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="headingThree">
                    <h5 class="mb-0">
                        <a class=" collapsed" role="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            <span> {l s='How does the Shoppingfeed Prestashop Plugin (Feed&Order) work ?' mod='shoppingfeed'}</span>
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </h5>
                </div>
                <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                    <div class="card-body">{l s='The module has two modes :' mod='shoppingfeed'} <br/>
                        <ul>
                            <li>{l s='the "real-time synchronization" mode, in this case for each update of the stock of a product for example if you manually modify or if a command goes into the status that restock, the module will send the information to Shopping Feed which will update the marketplaces. But some merchant who handles many orders and many products cannot multiply calls on the Shopping Feed API.' mod='shoppingfeed'}</li>
                            <li>{l s='cron job synchronization mode, in this case the inventory update is queued and you must program a cron job to perform this task that can be lengthy. Inventory updates are then grouped together for better performance.' mod='shoppingfeed'}</li>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="heading5">
                    <h5 class="mb-0">
                        <a class="collapsed" role="button" data-toggle="collapse" data-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                            <span> {l s='What are the recommendations for configuring the real time ?' mod='shoppingfeed'}</span>
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </h5>
                </div>
                <div id="collapse5" class="collapse" aria-labelledby="heading5" data-parent="#accordion">
                    <div class="card-body">
                        <ul>
                            <li>{l s='You activated multishop and have several Shopping feed account, the RealTime parameter on YES is recommended.' mod='shoppingfeed'}</li>
                            <li>{l s='You have less than 100 products, the RealTime parameter on YES is recommended. You have little stock for each reference and for you the stock precision is fundamental. Moreover, no need to set up any cron job. Sending real-time inventory updates to the Feed API makes it easy for you to sync inventory in less than 15 minutes. However, this multiplies the calls to the Shopping API stream which can slow the loading time of pages that decrement or increment the stock, especially during order status updates.' mod='shoppingfeed'}</li>
                            <li>{l s='You have between 100 and 1000 products, the Realtime parameter on NO is recommended. Updates are queued and the configuration of a cron job (URL) every 5 minutes will allow you to synchronize of all products waiting for synchronization. This reduce calls sent to the Shopping Flux API and improve page loading performances.' mod='shoppingfeed'}</li>
                            <li>{l s='You have more than 1000 products, Realtime parameter NO is required. You probably use an external tool (like an ERP) to manage your inventory which can lead to many updates at the same time. In this case, the updates are queued and the configuration of a cron job (URL) every 5 minutes will allow you to synchronize of all products waiting for synchronization. This reduce calls sent to the Shopping Flux API and improve page loading performances' mod='shoppingfeed'}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading2">
                    <h5 class="mb-0">
                        <a class="btn btn-link collapsed" role="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <span> {l s='Does the Shoppingfeed Prestashop Plugin (Feed&Order) synchronize other data from my product listings ?' mod='shoppingfeed'}
                           </span><i class="fa fa-chevron-up"></i>
                        </a>
                    </h5>
                </div>
                <div id="collapseTwo" class="collapse" aria-labelledby="heading2" data-parent="#accordion">
                    <div class="card-body">
                        {l s='Yes. Your stocks and prices are sync every 15 minutes. But you catalog is crawled by Shoppingfeed each day.' mod='shoppingfeed'}
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading7">
                    <h5 class="mb-0">
                        <a class="btn btn-link collapsed" role="button" data-toggle="collapse" data-target="#collapse7" aria-expanded="false" aria-controls="collapseTwo">
                            <span> {l s='Can I manage carriers from a marketplace?' mod='shoppingfeed'}
                           </span><i class="fa fa-chevron-up"></i>
                        </a>
                    </h5>
                </div>
                <div id="collapse7" class="collapse" aria-labelledby="heading7" data-parent="#accordion">
                    <div class="card-body">
                        {l s='Yes. You can map carrier from marketplaces with your PrestaShop carrier. Please consider a first order should be imported in your PrestaShop before seing a marketplace carrier. After that, you could go to Settings > Order Feed tab to configure your carrier.' mod='shoppingfeed'}
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading7">
                    <h5 class="mb-0">
                        <a class="btn btn-link collapsed" role="button" data-toggle="collapse" data-target="#collapse7" aria-expanded="false" aria-controls="collapseTwo">
                            <span> {l s='Can I manage ISBN, EAN13, MPN or my own reference in my product feed?' mod='shoppingfeed'}
                           </span><i class="fa fa-chevron-up"></i>
                        </a>
                    </h5>
                </div>
                <div id="collapse7" class="collapse" aria-labelledby="heading7" data-parent="#accordion">
                    <div class="card-body">
                        {l s='Yes, but this configuration cannot be modify after publishing your feed on marketplaces. Shoppingfeed support can help you to define your strategy.' mod='shoppingfeed'}
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading6">
                    <h5 class="mb-0">
                        <a class="collapsed" role="button" data-toggle="collapse" data-target="#collapse6" aria-expanded="false" aria-controls="collapse5">
                            <span> {l s='How can I configure my cron task ?' mod='shoppingfeed'}</span>
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </h5>
                </div>
                <div id="collapse6" class="collapse" aria-labelledby="heading6" data-parent="#accordion">
                    <div class="card-body">
                        <p>{l s='Only two cron tasks call are mendatory for all shops.' mod='shoppingfeed'}</p>
                        <p>{l s='Please contact your system admin or your webhost to configure this line.' mod='shoppingfeed'}</p>
                        <pre>
*/5 * * * *     curl -s {$syncProductUrl|escape:'htmlall':'UTF-8'}  >/dev/null
*/5 * * * *     curl -s {$syncOrderUrl|escape:'htmlall':'UTF-8'}  >/dev/null
</pre>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading4">
                    <h5 class="mb-0">
                        <a class=" collapsed" role="button" data-toggle="collapse" data-target="#collapse4" aria-expanded="false" aria-controls="collapseThree">
                            <span>{l s='How to contact support ?' mod='shoppingfeed'}</span>
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </h5>
                </div>
                <div id="collapse4" class="collapse" aria-labelledby="heading4" data-parent="#accordion">
                    <div class="card-body">{l s='To contact the support, go on our Prestashop Addons module page, click on "Contact the developer" button and send us' mod='shoppingfeed'}
                        {l s='a message describing your issue with the following information :' mod='shoppingfeed'}
                        <br/>
                        <br/>
                        <textarea readonly rows="10" >
URL: {$shop_url|escape:'htmlall':'UTF-8'}
Version PHP: {$php_version|escape:'htmlall':'UTF-8'}
Version PrestaShop: {$prestashop_version|escape:'htmlall':'UTF-8'}
Version ShoppingFeed: {$module_version|escape:'htmlall':'UTF-8'}
Multishop: {$multishop|escape:'htmlall':'UTF-8'}
Combination: {$combination|escape:'htmlall':'UTF-8'}

Products: {$nbr_products|escape:'htmlall':'UTF-8'}
Token : {$token|escape:'htmlall':'UTF-8'}
Real time active: {$REAL_TIME_SYNCHRONIZATION|escape:'htmlall':'UTF-8'}
Real time count products: {$STOCK_SYNC_MAX_PRODUCTS|escape:'htmlall':'UTF-8'}
Cron product url: {$syncProductUrl|escape:'htmlall':'UTF-8'}
Cron order url: {$syncOrderUrl|escape:'htmlall':'UTF-8'}
Last sync: {$LAST_CRON_TIME_SYNCHRONIZATION|escape:'htmlall':'UTF-8'}</textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>
{/block}

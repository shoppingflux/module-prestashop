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
                           <span>{l s='Does the 15 min Marketplace Updates - Shopping Feed module work with the official Shopping Feed module?' mod='shoppingfeed'}
                           </span>
                           <i class="fa fa-chevron-up"></i>
                       </a>
                   </h5>
               </div>

               <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                   <div class="card-body">
{l s='Yes. This new module works with or without the other module' mod='shoppingfeed'}
                   </div>
               </div>
           </div>
           <div class="card">
               <div class="card-header" id="headingTwo">
                   <h5 class="mb-0">
                       <a class="btn btn-link collapsed" role="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <span> {l s='Does the 15 min Marketplace Updates - Shopping Feed module synchronize other data from my product listings ?' mod='shoppingfeed'}
                           </span><i class="fa fa-chevron-up"></i>
                       </a>
                   </h5>
               </div>
               <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                   <div class="card-body">
{l s='No. For the moment this module only synchronizes stocks, but we are already thinking about adding price updates on the same principle as stocks' mod='shoppingfeed'}
                   </div>
               </div>
           </div>
           <div class="card">
               <div class="card-header" id="headingThree">
                   <h5 class="mb-0">
                       <a class=" collapsed" role="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            <span> {l s='How does the 15 min Marketplace Updates - Shopping Feed work ?' mod='shoppingfeed'}</span>
                           <i class="fa fa-chevron-up"></i>
                       </a>
                   </h5>
               </div>
               <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                   <div class="card-body">{l s='The module has two modes :' mod='shoppingfeed'} </br>
                       {l s='- the "real-time synchronization" mode, in this case for each update of the stock of a product for example if you manually modify or if a command goes into the status that destock, the module will send the information to Shopping Feed which will update the marketplaces. But some merhant who handles many orders and many products cannot multiply calls on the Shopping Feed API.' mod='shoppingfeed'} </br>
{l s='- cron job syncronization mode, in this case the inventory update is queued and you must program a cron job to perform this task that can be lenghty. Inventory updates are then grouped together for better performance.' mod='shoppingfeed'}
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
                       </br>
                       </br>
                       <textarea readonly rows="10" >
URL : {$shop_url}
Version PHP : {$php_version}
Version PrestaShop : {$prestashop_version}
Multiboutique activé : {$multiboutique}
Nombre de produits en base de données : {$nbr_prpoducts}
Token : {$token}
Configuration temps réel : {$REAL_TIME_SYNCHRONIZATION}
Nombre de produits : {$STOCK_SYNC_MAX_PRODUCTS}
Date du dernier lancement du cron : {$LAST_CRON_TIME_SYNCHRONIZATION}</textarea>
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

                   </div>
               </div>
           </div>

       </div>
   </div>
{/block}
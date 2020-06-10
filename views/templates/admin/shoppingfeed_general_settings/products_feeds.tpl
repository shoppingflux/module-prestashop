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
<div class="panel kpi-container">
    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <a style="display:block" href="#" id="box-disabled-products" data-toggle="tooltip" class="box-stats label-tooltip color4" data-original-title="{l s='You can also launch product task on page Logs & Crons.' mod='shoppingfeed'}">
                <div class="kpi-content">
                    <i class="icon-off"></i><span class="title">{l s='Product sync task' mod='shoppingfeed'}</span>
                    <span class="subtitle">{l s='Last launch' mod='shoppingfeed'} 20/06/2020 13:09</span>
                    <span class="value">{l s='active' mod='shoppingfeed'}</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a style="display:block" href="#" id="box-products-stock" data-toggle="tooltip" class="box-stats label-tooltip color1" data-original-title="{l s='Disabled products are not in the feed.' mod='shoppingfeed'}">
                <div class="kpi-content">
                    <i class="icon-archive"></i>
                    <span class="title">{l s='Exported products' mod='shoppingfeed'}</span>
                    <span class="subtitle">{l s='from your catalog' mod='shoppingfeed'}</span>
                    <span class="value">{$count_products|default:0}</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div id="box-avg-gross-margin" data-toggle="tooltip" class="box-stats label-tooltip color2" data-original-title="Pour éviter de calculer le flux produit au moment de son appel, le flux de produit est mis en cache .">
                <div class="kpi-content">
                    <i class="icon-beaker"></i>
                    <span class="title">{l s='Product feed preloading' mod='shoppingfeed'}</span>
                    <span class="value">57% {l s='of your catalog' mod='shoppingfeed'}</span>
                    <span class="subtitle">{l s='Go to your XML feed' mod='shoppingfeed'}</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a style="display:block" href="#" id="box-8020-sales-catalog" data-toggle="tooltip" class="box-stats label-tooltip color3" data-original-title="Le cache d'un produit est automatiquement recalculé s'il excède 7 jours.">
                <div class="kpi-content">
                    <i class="icon-tag"></i>
                    <span class="title">{l s='Cache refreshing' mod='shoppingfeed'}</span>
                    <span class="subtitle"></span>
                    <span class="value">{l s='7 days' mod='shoppingfeed'}</span>
                    <span class="subtitle">{l s='Purge cache' mod='shoppingfeed'}</span>
                </div>
            </a>
        </div>
    </div>
</div>

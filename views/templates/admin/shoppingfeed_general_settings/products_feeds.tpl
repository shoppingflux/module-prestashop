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
        <div class="col-sm-6 col-lg-4">
            <a style="display:block" href="{$link->getAdminLink('AdminShoppingfeedProcessMonitor', true)}" id="box-disabled-products" data-toggle="tooltip" class="box-stats label-tooltip {if $syncProduct->id == null || $syncProduct->last_update > date('Y-m-d H:i:s', (time() - 3660 * 24))}color2{else}color4{/if}" data-original-title="{l s='You can also launch product task on page Logs & Crons.' mod='shoppingfeed'}">
                <div class="kpi-content">
                    <i class="icon-off"></i><span class="title">{l s='Product sync task' mod='shoppingfeed'}</span>
                    {if $syncProduct->id == null || $syncProduct->last_update < date('Y-m-d H:i:s', (time() - 3600 * 24))}
                    <span class="value">{l s='inactive' mod='shoppingfeed'}</span>
                    {else}
                    <span class="subtitle">{l s='Last launch' mod='shoppingfeed'} {$syncProduct->last_update}</span>
                    <span class="value">{l s='active' mod='shoppingfeed'}</span>
                    {/if}
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-4">
            <a href="{$link->getModuleLink('shoppingfeed', 'product')|escape:'html'}" id="box-products-stock" data-toggle="tooltip" class="box-stats label-tooltip color1" data-original-title="{l s='Disabled products are not in the feed.' mod='shoppingfeed'}">
                <div class="kpi-content">
                    <i class="icon-archive"></i>
                    <span class="title">{l s='Exported products' mod='shoppingfeed'}</span>
                    <span class="value">{$count_products|default:0}</span>
                    <span class="subtitle">{l s='Go to your XML feed' mod='shoppingfeed'}</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-4">
            <a id="purge-cache" href="#" data-link-purge-cache="{{$link->getAdminLink('AdminShoppingfeedGeneralSettings')}}" id="box-avg-gross-margin" data-toggle="tooltip" class="box-stats label-tooltip {if $percent_preloading < 99}color2{else}color4{/if}" data-original-title="{l s='To avoid live computation of your feed during its call, your product are indexed in a cache system.' mod='shoppingfeed'}">
                <div class="kpi-content">
                    <i class="icon-beaker"></i>
                    <span class="title">{l s='Product feed indexing' mod='shoppingfeed'}</span>
                    <span class="value">{$percent_preloading|default:0}% {l s='of your catalog' mod='shoppingfeed'}</span>
                    <span class="subtitle">{l s='Purge cache' mod='shoppingfeed'}</span>
                </div>
            </a>
        </div>
    </div>
</div>

{if $count_products == 0}
<div class="alert alert-danger">
    <strong>{l s='The filter applied will not return any product in your feed, please correct your filter.' mod='shoppingfeed'}</strong>
</div>
{else if $percent_preloading < 99}
    {if $hasAFilter}
        <div class="alert alert-warning">
            <strong>{l s='Due to the application of a filter, not all products in your catalog are sent to Shopping Feed.' mod='shoppingfeed'}</strong>
        </div>
    {else}
    <div class="alert alert-danger">
        <strong>{l s='Your products feed is not totally indexed.' mod='shoppingfeed'}</strong><br>
        {l s='To indexed missing products, please launch manually or set on your server the following task: "Synchronize products on Shopping Feed" available on' mod='shoppingfeed'} <a href="{$link->getAdminLink('AdminShoppingfeedProcessMonitor', true)}">{l s='"Logs & Crons" pages' mod='shoppingfeed'}</a>.
    </div>
    {/if}
{/if}

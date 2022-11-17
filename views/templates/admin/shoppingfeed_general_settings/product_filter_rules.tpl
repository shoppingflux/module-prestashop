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
<div id="product_rule_form" class="col-lg-12 bootstrap">
    <div class="col-lg-6">
        {l s='Unselected' mod='shoppingfeed'}
        <select multiple size="10" id="product_rule_unselect" class="product_rule_unselect">
			{foreach from=$products.unselected item='item'}
				<option value="{$item.id|intval}" title="{$item.name|escape:'html':'UTF-8'}">&nbsp;{$item.name|escape:'html':'UTF-8'}</option>
			{/foreach}
        </select>
        <div class="clearfix">&nbsp;</div>
        <a id="product_rule_select_add" class="btn btn-default btn-block" >
            {l s='Add' mod='shoppingfeed'}
        <i class="icon-arrow-right"></i>
        </a>
    </div>
    <div class="col-lg-6">
        {l s='Selected' mod='shoppingfeed'}
        <select multiple size="10" id="product_rule_select" class="product_rule_toselect" >
            {foreach from=$products.selected item='item'}
				<option value="{$item.id|intval}" title="{$item.name|escape:'html':'UTF-8'}">&nbsp;{$item.name|escape:'html':'UTF-8'}</option>
			{/foreach}
        </select>
        <div class="clearfix">&nbsp;</div>
        <a id="product_rule_select_remove" class="btn btn-default btn-block" >
        <i class="icon-arrow-left"></i>
        {l s='Remove' mod='shoppingfeed'}
        </a>
    </div>
</div>

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

{block name='input_row'}
    {if $input.type == 'shoppingfeed_alert'}
        {if !isset($input.condition) || $input.condition}
            <div class="alert alert-{$input.severity|escape:'htmlall':'UTF-8'}">
                {$input.message nofilter}
            </div>
        {/if}
    {elseif $input.type == 'shoppingfeed_open-section'}
        <div id="{$input.id|escape:'htmlall':'UTF-8'}" class="shoppingfeed_form-section">
            {if isset($input.title)}
                <h2>{$input.title nofilter}</h2>
            {/if}

            {if isset($input.desc)}
            <div class="alert alert-info">
                {$input.desc|escape:'htmlall':'UTF-8'}
            </div>
            {/if}
    {elseif $input.type == 'shoppingfeed_close-section'}
        </div>
    {elseif $input.type == 'shoppingfeed_carrier-matching'}
    <div class="form-group">
        <label class="control-label col-lg-3">
            <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="{l s='Carrier used when an order is imported from a new marketplace.' mod='shoppingfeed'}">{l s='Default carrier' mod='shoppingfeed'}</span>
        </label>
        <div class="col-lg-9">
            <select name="{$input.default_carrier_field_name|escape:'htmlall':'UTF-8'}" class="fixed-width-xl">
                {foreach from=$input.carriers item='option'}
                    <option value="{$option.value|escape:'htmlall':'UTF-8'}" {if $fields_value[$input.default_carrier_field_name] == $option.value}selected="selected"{/if}>&nbsp;{$option.label|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
    </div>
        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Carriers matching' mod='shoppingfeed'}
            </label>
            <div class="col-lg-9">
                <table class="table" id="shoppingfeed_carriers-matching-table">
                    <thead>
                        <tr>
                            <th><span class="title_box active">{l s='Shopping feed carrier' mod='shoppingfeed'}</span></th>
                            <th><span class="title_box active">{l s='Prestashop carrier' mod='shoppingfeed'}</span></th>
                        </tr>
                        <tr class="nodrag nodrop filter row_hover">
                            <th class="text-center">

                                <select class="fixed-width-xl" id="shoppingfeed_marketplace-filter">
                                        <option value="">&nbsp;{l s='All' mod='shoppingfeed'}</option>
                                    {foreach from=$input.marketplace_filter_options item='option'}
                                        <option value="{$option.value|escape:'htmlall':'UTF-8'}">&nbsp;{$option.label|escape:'htmlall':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                            </th>
                            <th class="text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$input.shoppingfeed_carriers item='sfCarrier' key='key_sfCarrier'}
                            <tr data-shoppingfeed-marketplace="{$sfCarrier->name_marketplace|escape:'htmlall':'UTF-8'}">
                                <td>{$input.carriers_matching_field.labels[$key_sfCarrier]|escape:'htmlall':'UTF-8'}</td>
                                <td>
                                    <select name="{$input.carriers_matching_field.name|escape:'htmlall':'UTF-8'}[{$sfCarrier->id|escape:'htmlall':'UTF-8'}]">
                                        {foreach from=$input.carriers item='option'}
                                            <option value="{$option.value|escape:'htmlall':'UTF-8'}" {if $sfCarrier->id_carrier_reference == $option.value}selected="selected"{/if}>&nbsp;{$option.label|escape:'htmlall':'UTF-8'}</option>
                                        {/foreach}
                                    </select>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="input" append}
    {if $input.type == 'shoppingfeed_double-list'}
        <div class="row shoppingfeed_double-list-group">
            <div class="col-lg-6 shoppingfeed_double-list-left-container">
                <span class="bold">{$input.unselected.label|escape:'htmlall':'UTF-8'}</span>
                <select id="{$input.unselected.id|escape:'htmlall':'UTF-8'}" class="input-large shoppingfeed_double-list-unselected" multiple>
                    {foreach from=$input.unselected.options item='option'}
                        <option value="{$option.value|escape:'htmlall':'UTF-8'}">&nbsp;{$option.label|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <a id="{$input.unselected.btn.id|escape:'htmlall':'UTF-8'}" class="shoppingfeed_double-list-btn-select btn btn-default btn-block clearfix" >{$input.unselected.btn.label|escape:'htmlall':'UTF-8'} <i class="icon-arrow-right"></i></a>
            </div>

            <div class="col-lg-6 shoppingfeed_double-list-right-container">
                <span class="bold">{$input.selected.label|escape:'htmlall':'UTF-8'}</span>
                <select id="{$input.selected.id|escape:'htmlall':'UTF-8'}" class="input-large  shoppingfeed_double-list-selected" multiple>
                    {foreach from=$input.selected.options item='option'}
                        <option value="{$option.value|escape:'htmlall':'UTF-8'}">&nbsp;{$option.label|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <a id="{$input.selected.btn.id|escape:'htmlall':'UTF-8'}" class="shoppingfeed_double-list-btn-unselect btn btn-default btn-block clearfix"><i class="icon-arrow-left"></i> {$input.selected.btn.label|escape:'htmlall':'UTF-8'}</a>
            </div>

            <div class="shoppingfeed_double-list-values hidden">
                {foreach from=$input.unselected.options item='option'}
                    <input type="checkbox" name="{$input.name|escape:'htmlall':'UTF-8'}[]" value="{$option.value|escape:'htmlall':'UTF-8'}"/>
                {/foreach}
                {foreach from=$input.selected.options item='option'}
                    <input type="checkbox" name="{$input.name|escape:'htmlall':'UTF-8'}[]" value="{$option.value|escape:'htmlall':'UTF-8'}" checked/>
                {/foreach}
            </div>
        </div>
    {/if}
{/block}

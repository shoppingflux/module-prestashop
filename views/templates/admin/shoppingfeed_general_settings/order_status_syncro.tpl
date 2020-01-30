{extends file="helpers/form/form.tpl"}

{block name='input_row'}
    {if $input.type == 'shoppingfeed_alert'}
        {if !isset($input.condition) || $input.condition}
            <div class="alert alert-{$input.severity}">
                {$input.message}
            </div>
        {/if}
    {elseif $input.type == 'shoppingfeed_open-section'}
        <div id='{$input.id}' class='shoppingfeed_form-section'>
            {if isset($input.title)}
                <h2>{$input.title}</h2>
            {/if}
            
            {if isset($input.desc)}
                <p>{$input.desc}</p>
            {/if}
    {elseif $input.type == 'shoppingfeed_close-section'}
        </div>
    {elseif $input.type == 'shoppingfeed_carrier-matching'}
        <div class="form-group">
            <div class="col-lg-6">
                <label class="control-label col-lg-2">
                    {l s='Marketplace' mod='shoppingfeed'}
                </label>
                <div class="col-lg-3">
                    <select class="fixed-width-xl" id="shoppingfeed_marketplace-filter">
                            <option value="">&nbsp;{l s='All' mod='shoppingfeed'}</option>
                        {foreach from=$input.marketplace_filter_options item='option'}
                            <option value="{$option.value}">&nbsp;{$option.label|escape}</option>
                        {/foreach}
                    </select>
                </div>
                <label class="control-label col-lg-2">
                    {l s='Default carrier' mod='shoppingfeed'}
                </label>
                <div class="col-lg-3">
                    <select name="{$input.default_carrier_field_name}" class="fixed-width-xl">
                        {foreach from=$input.carriers item='option'}
                            <option value="{$option.value}" {if $fields_value[$input.default_carrier_field_name] == $option.value}selected="selected"{/if}>&nbsp;{$option.label|escape}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="col-xs-10">
                <table class="table" id="shoppingfeed_carriers-matching-table">
                    <thead>
                        <tr>
                            <th>{l s='Shopping feed carrier' mod='shoppingfeed'}</th>
                            <th>{l s='Prestashop carrier' mod='shoppingfeed'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$input.shoppingfeed_carriers item='sfCarrier' key='key_sfCarrier'}
                            <tr data-shoppingfeed-marketplace="{$sfCarrier->name_marketplace}">
                                <td>{$input.carriers_matching_field.labels[$key_sfCarrier]}</td>
                                <td>
                                    <select name="{$input.carriers_matching_field.name}[{$sfCarrier->id}]">
                                        {foreach from=$input.carriers item='option'}
                                            <option value="{$option.value}" {if $sfCarrier->id_carrier_reference == $option.value}selected="selected"{/if}>&nbsp;{$option.label|escape}</option>
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
                <span class="bold">{$input.unselected.label}</span>
                <select id="{$input.unselected.id}" class="input-large shoppingfeed_double-list-unselected" multiple>
                    {foreach from=$input.unselected.options item='option'}
                        <option value="{$option.value}">&nbsp;{$option.label|escape}</option>
                    {/foreach}
                </select>
                <a id="{$input.unselected.btn.id}" class="shoppingfeed_double-list-btn-select btn btn-default btn-block clearfix" >{$input.unselected.btn.label} <i class="icon-arrow-right"></i></a>
            </div>

            <div class="col-lg-6 shoppingfeed_double-list-right-container">
                <span class="bold">{$input.selected.label}</span>
                <select id="{$input.selected.id}" class="input-large  shoppingfeed_double-list-selected" multiple>
                    {foreach from=$input.selected.options item='option'}
                        <option value="{$option.value}">&nbsp;{$option.label|escape}</option>
                    {/foreach}
                </select>
                <a id="{$input.selected.btn.id}" class="shoppingfeed_double-list-btn-unselect btn btn-default btn-block clearfix"><i class="icon-arrow-left"></i> {$input.selected.btn.label}</a>
            </div>
            
            <div class="shoppingfeed_double-list-values hidden">
                {foreach from=$input.unselected.options item='option'}
                    <input type="checkbox" name="{$input.name}[]" value="{$option.value}"/>
                {/foreach}
                {foreach from=$input.selected.options item='option'}
                    <input type="checkbox" name="{$input.name}[]" value="{$option.value}" checked/>
                {/foreach}
            </div>
        </div>
    {/if}
{/block}
{extends file="helpers/form/form.tpl"}

{block name='input_row'}
    {if $input.type == 'alert'}
        <div class="alert alert-{$input.severity}">
            {$input.message}
        </div>
    {elseif $input.type == 'open_section'}
        <div id='{$input.id}' class='shoppingfeed_form-section'>
            {if isset($input.title)}
                <h2>{$input.title}</h2>
            {/if}
            
            {if isset($input.desc)}
                <p>{$input.desc}</p>
            {/if}
    {elseif $input.type == 'close_section'}
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
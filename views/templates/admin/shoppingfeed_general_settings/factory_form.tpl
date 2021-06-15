{extends file="helpers/form/form.tpl"}
{block name="input" append}
    {if $input.type == 'number'}
        <div class="row">
           			<input type="number"
                        name="{$input.name}"
                        id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
                        value="{$fields_value[$input.name]}"
                        class="{if isset($input.class)}{$input.class}{/if}{if $input.type == 'tags'} tagify{/if}"
                        {if isset($input.readonly) && $input.readonly} readonly="readonly"{/if}
                        {if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
                        min="0"
                        />
        </div>
    {/if}
{/block}

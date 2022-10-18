{extends file="helpers/form/form.tpl"}
{block name="input" append}
    {if $input.type == 'number'}
        <div class="row">
           			<input type="number"
                        name="{$input.name|escape:'html':'UTF-8'}"
                        id="{if isset($input.id)}{$input.id|escape:'html':'UTF-8'}{else}{$input.name|escape:'html':'UTF-8'}{/if}"
                        value="{$fields_value[$input.name]|escape:'html':'UTF-8'}"
                        class="{if isset($input.class)}{$input.class|escape:'html':'UTF-8'}{/if}{if $input.type == 'tags'} tagify{/if}"
                        {if isset($input.readonly) && $input.readonly} readonly="readonly"{/if}
                        {if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
                        min="0"
                        />
        </div>
    {/if}
{/block}

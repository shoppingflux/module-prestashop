<div id="product_rule_form" class="col-lg-12 bootstrap">
    <div class="col-lg-6">
        {l s='Unselected'}
        <select multiple size="10" id="product_rule_unselect" class="product_rule_unselect">
			{foreach from=$products.unselected item='item'}
				<option value="{$item.id|intval}" title="{$item.name}">&nbsp;{$item.name}</option>
			{/foreach}
        </select>
        <div class="clearfix">&nbsp;</div>
        <a id="product_rule_select_add" class="btn btn-default btn-block" >
            {l s='Add'}
        <i class="icon-arrow-right"></i>
        </a>
    </div>
    <div class="col-lg-6">
        {l s='Selected'}
        <select multiple size="10" id="product_rule_select" class="product_rule_toselect" >
            {foreach from=$products.selected item='item'}
				<option value="{$item.id|intval}" title="{$item.name}">&nbsp;{$item.name}</option>
			{/foreach}
        </select>
        <div class="clearfix">&nbsp;</div>
        <a id="product_rule_select_remove" class="btn btn-default btn-block" >
        <i class="icon-arrow-left"></i>
        {l s='Remove'}
        </a>
    </div>
</div>
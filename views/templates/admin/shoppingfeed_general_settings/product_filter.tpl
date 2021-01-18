<table id="product_rule" class="table table-bordered hidden">
    <tbody>
        <tr>
            <td class="type">
            </td>
            <td class="product_filter">
                <input type="text" value="" disabled="disabled" />
            </td>
            <td class="product_input">
                <a class="btn btn-default product_rule_choose_link" >
                    <i class="icon-list-ul"></i>
                </a>
                <input type="hidden" />
            </td>
            <td class="text-right">
                <a class="btn btn-default product_rule_remove" >
                <i class="icon-remove"></i>
                </a>
            </td>
        </tr>
    </tbody>
</table>

<form id="configuration_form" class="defaultForm form-horizontal AdminShoppingfeedGeneralSettings" method="post" enctype="multipart/form-data" novalidate="">
    <input type="hidden" name="submitAddconfiguration" value="1">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cog"></i>Sélection de produits
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Add a rule concerning'}
                </label>
                <div class="col-lg-9">
                    <select class="fixed-width-xl" id="product_rule_type">
		                <option value="">{l s='-- Choose --'}</option>
                        <option value="products">{l s='Products'}</option>
                        <option value="attributes">{l s='Attributes'}</option>
                        <option value="categories">{l s='Categories'}</option>
                        <option value="manufacturers">{l s='Manufacturers'}</option>
                        <option value="suppliers">{l s='Suppliers'}</option>
                        <option value="features">{l s='Features'}</option>
                    </select>
                    <a id="buttonAddProductRuleGroup" class="btn btn-default ">
					    <i class="icon-plus-sign"></i> Sélection de produit
				    </a>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">
                </label>
                <div class="col-lg-9">
                    <table id="product_rule_table" class="table table-bordered">
                        <tbody>
                            {foreach from=$product_filters key=type item=product_filter}
                            <tr data-type="{$type}">
                                <td class="type">
                                    {if $type === 'products'}
                                        {l s='Products'}
                                    {elseif $type === 'attributes'}
                                        {l s='Attributes'}
                                    {elseif $type === 'categories'}
                                        {l s='Categories'}
                                    {elseif $type === 'manufacturers'}
                                        {l s='Manufacturers'}
                                    {elseif $type === 'suppliers'}
                                        {l s='Suppliers'}
                                    {elseif $type === 'features'}
                                        {l s='Features'}
                                    {/if}
                                </td>
                                <td class="product_filter">
                                    <input type="text" value="" disabled="disabled" />
                                </td>
                                <td class="product_input">
                                    <a class="btn btn-default product_rule_choose_link" >
                                        <i class="icon-list-ul"></i>
                                    </a>
                                    <input type="hidden" name="product_rule_select[{$type}][]" value="{$product_filter}"/>
                                </td>
                                <td class="text-right">
                                    <a class="btn btn-default product_rule_remove" >
                                    <i class="icon-remove"></i>
                                    </a>
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-group form-check">
                <label class="control-label col-lg-3">
                    {l s='Send products depending to their visibility'}
                </label>
                <div class="col-lg-9">
					<div class="checkbox">
						<label for="product_visibility_nowhere">
							<input type="checkbox" name="product_visibility_nowhere" id="product_visibility_nowhere" value="1" {if $product_visibility_nowhere}checked="checked" {/if}>
                        </label>
					</div>
				</div>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" value="1" name="saveFeedFilterConfig" class="btn btn-default pull-right">
                <i class="process-icon-save"></i>Sauvegarder
            </button>
        </div>
    </div>
</form>
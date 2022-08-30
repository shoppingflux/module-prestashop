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
            <i class="icon-cog"></i> {l s='Products selection' mod='shoppingfeed'}
        </div>
        <div class="form-wrapper">
          <div class="shoppingfeed_form-section">
            <h2>{l s='Choose the products send to Shopping Feed' mod='shoppingfeed'}</h2>

            <div id="condition-container"></div>
          </div>

            <div class="shoppingfeed_form-section">
                <h2>{l s='Send products depending to their visibility' mod='shoppingfeed'}</h2>
                <div class="form-group form-check">
                    <label class="control-label col-lg-3">
                        {l s="Send to Shopping Feed products with visibility 'Nowhere'" mod='shoppingfeed'}
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
        </div>
        <div class="panel-footer">
            <button type="submit" value="1" name="saveFeedFilterConfig" class="btn btn-default pull-right">
                <i class="process-icon-save"></i>{l s='Save'}
            </button>
        </div>
    </div>
</form>

<script>
  var ruleGenerator = new RuleConditionGenerator({});
  ruleGenerator.init(document.getElementById('condition-container'));

  {foreach from=$product_filters item=groupFilter}
      ruleGenerator.addNewConditionSet();
      {foreach from=$groupFilter item=filter}
          ruleGenerator.addCondition({
              filter: '{$filter->getFilter() nofilter}',
              type: '{$filter->getType()|escape:'htmlall':'utf-8'}',
              value: '{$filter->getValue()|escape:'htmlall':'utf-8'}'
          });
      {/foreach}
  {/foreach}


</script>

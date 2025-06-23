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
                <i class="process-icon-save"></i>{l s='Save'  mod='shoppingfeed'}
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
              filter: '{$filter->getFilter()|escape:'htmlall':'UTF-8'}',
              type: '{$filter->getType()|escape:'htmlall':'UTF-8'}',
              value: '{$filter->getValue()|escape:'htmlall':'UTF-8'}'
          });
      {/foreach}
  {/foreach}


</script>

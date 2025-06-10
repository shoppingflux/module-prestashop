{**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *}
<div class="w-100 mb-3">
  <div class="row">
    <div class="col-sm-12">
      <div class="row justify-content-center">
        <div class="col-xl-12 pr-5 pl-5">
          <div class="card">
            <div class="card-header d-flex justify-content-between">
              <div>
                <span class="material-icons">webhook</span>
                  {l s='Hooks' mod='shoppingfeed'}
              </div>
                {if empty($hooksOnError)}
                  <div class="badge-success px-2 mb-0">{l s='All hooks are registered' mod='shoppingfeed'}</div>
                {else}
                  <div class="badge-danger px-2 mb-0">{l s='Unregistered hooks found' mod='shoppingfeed'}</div>
                {/if}
            </div>
            <div class="form-wrapper justify-content-center col-xl-12 mt-3 {if empty($hooksOnError)}d-none{/if}">
              <div class="mt-2 alert alert-info">{l s='Check if hooks of %s module are all plugged.' sprintf=[$module_name|escape:'html':'UTF-8'] mod='shoppingfeed'}</div>
              <div class="table-wrapper">
                <table class="table border-bottom">
                  <thead>
                  <tr>
                    <th>#</th>
                    <th>{l s='Hook name' mod='shoppingfeed'}</th>
                      {foreach $shopList as $shop}
                        <th>{l s='Shop' mod='shoppingfeed'}: {$shop.name|escape:'html':'UTF-8'}</th>
                      {/foreach}
                    <th>{l s='Actions' mod='shoppingfeed'}</th>
                  </tr>
                  </thead>
                  <tbody>
                  {foreach $hooks as $index => $hook}
                      {assign var='shouldBeFixed' value=false}
                    <tr>
                      <td>{$index + 1}</td>
                      <td>{$hook.name|escape:'html':'UTF-8'}</td>
                        {foreach $shopList as $shop}
                            {foreach $hook.shops as $hookShop}
                                {if $shop.id_shop == $hookShop.id}
                                  <td>
                                      {if $hookShop.value}
                                        <span class="badge badge-success">{l s='OK' mod='shoppingfeed'}</span>
                                      {else}
                                          {assign var='shouldBeFixed' value=true}
                                        <a href="{$actionsLink|cat:'&id_shop='|cat:$hookShop.id|cat:'&hookName='|cat:$hook.name|cat:'&event=fixHook'|escape:'html':'UTF-8'}">
                                          <span class="badge badge-danger">{l s='KO' mod='shoppingfeed'}</span>
                                        </a>
                                      {/if}
                                  </td>
                                {/if}
                            {/foreach}
                        {/foreach}
                      <td>
                          {if !$shouldBeFixed}
                            -
                          {else}
                            <a class="btn btn-danger py-0"
                               style="color: #fff;"
                               href="{$actionsLink|cat:'&hookName='|cat:$hook.name|cat:'&event=fixHooks'|escape:'html':'UTF-8'}">
                                {l s='Fix hook' mod='shoppingfeed'}
                            </a>
                          {/if}
                      </td>
                    </tr>
                  {/foreach}
                  </tbody>
                </table>
              </div>
                {if !empty($hooksOnError)}
                  <div class="mb-3">
                    <p>{l s='The next hooks should be activated for your module' mod='shoppingfeed'}</p>
                    <ul>
                        {foreach $hooksOnError as $hook}
                          <li>{$hook|escape:'html':'UTF-8'}</li>
                        {/foreach}
                    </ul>
                    <a class="btn btn-danger py-0"
                       style="color: #fff;"
                       href="{$actionsLink|cat:'&event=fixAllHooks'|escape:'html':'UTF-8'}">
                        {l s='Fix all hooks' mod='shoppingfeed'}
                    </a>
                  </div>
                {/if}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
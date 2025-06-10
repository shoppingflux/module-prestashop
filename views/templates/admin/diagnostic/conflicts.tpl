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
{if !empty($conflicts.data)}
  <div class="w-100 mb-3">
    <div class="row">
      <div class="col-sm-12">
        <div class="row justify-content-center">
          <div class="col-xl-12 pr-5 pl-5">
            <div class="card">
              <div class="card-header d-flex justify-content-between">
                <div>
                  <span class="material-icons">block</span>
                    {l s='Known conflicts (modules, configuration)' mod='shoppingfeed'}
                </div>
                {if empty($conflicts.data)}
                  <div class="badge-success px-2 mb-0">{l s='There is no conflicts' mod='shoppingfeed'}</div>
                {else}
                  <div class="badge-warning px-2 mb-0">{l s='Conflicts found' mod='shoppingfeed'}</div>
                {/if}
              </div>
              <div class="form-wrapper justify-content-center col-xl-12 mt-3 {if empty($conflicts.data)}d-none{/if}">
                <ul>
                  {foreach $conflicts.data as $conflict}
                      <li>{$conflict|escape:'html':'UTF-8'}</li>
                  {/foreach}
                </ul>
              </div>
             {if $conflicts.action|escape:'html':'UTF-8'}
               <div class="card-footer {if empty($conflicts.data)}d-none{/if}">
                 <div class="d-flex justify-content-end">
                   <a href="{$conflicts.action}" class="btn btn-lg btn-primary badge-info" type="submit">
                       {l s='Fix conflicts' mod='shoppingfeed'}
                   </a>
                 </div>
               </div>
             {/if}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
{/if}

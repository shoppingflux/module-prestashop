<div class="w-100 mb-3">
  <div class="row">
    <div class="col-sm-12">
      <div class="row justify-content-center">
        <div class="col-xl-12 pr-5 pl-5">
          <div class="card">
            <div class="card-header d-flex justify-content-between">
              <div>
                <span class="material-icons">folder</span>
                  {l s='PrestaShop Overrides' mod='shoppingfeed'}
              </div>
                {if empty($overrides)}
                  <div class="badge-success px-2 mb-0">{l s='There is no overrides' mod='shoppingfeed'}</div>
                {else}
                  <div class="badge-warning px-2 mb-0">{l s='Some overrides found' mod='shoppingfeed'}</div>
                {/if}
            </div>
            <div class="form-wrapper justify-content-center col-xl-12 mt-3 d-none">
              <div class="mt-2 alert alert-info">{l s='Overrides may cause conflits with Shoppingfeed modules. As all customizations, overrides get complexity to your PrestaShop.' mod='shoppingfeed'}</div>
              {foreach name="overrides" from=$overrides item=override}
                {if $smarty.foreach.overrides.first}
                  <p>
                    {l s='You\'ll found here the list of overrides.' mod='shoppingfeed'}
                  </p>
                  <ul>
                {/if}
                <li>{$override}</li>
                {if $smarty.foreach.overrides.last}
                  </ul>
                {/if}
              {foreachelse}
                <p>{l s='No overrides detected.' mod='shoppingfeed'}</p>
              {/foreach}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>



            
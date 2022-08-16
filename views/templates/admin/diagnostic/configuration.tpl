<div class="w-100 mb-3">
  <div class="row">
    <div class="col-sm-12">
      <div class="row justify-content-center">
        <div class="col-xl-12 pr-5 pl-5">
          <div class="card">
            <div class="card-header d-flex justify-content-between">
              <div>
                <span class="material-icons">toggle_on</span>
                  {l s='Settings integrity' mod='shoppingfeed'}
              </div>
                {if $allConfigurationsAreSame}
                  <div class="badge-success px-2 mb-0">{l s='All configurations are same' mod='shoppingfeed'}</div>
                {else}
                  <div class="badge-warning px-2 mb-0">{l s='Some configurations have different values' mod='shoppingfeed'}</div>
                {/if}
            </div>
            <div class="form-wrapper justify-content-center col-xl-12 mt-3 {if $allConfigurationsAreSame}d-none{/if}">
              <div class="mt-2 alert alert-info">{l s='Verify your Shoppingfeed module setting in a quick view on all shop.' mod='shoppingfeed'}</div>
              <table class="table border-bottom">
                <thead>
                <tr>
                  <th>{l s='Configuration' mod='shoppingfeed'}</th>
                  <th>{l s='All shops value' mod='shoppingfeed'}</th>
                    {foreach $shopList as $shop}
                      <th>{l s='Shop' mod='shoppingfeed'}: {$shop.name}</th>
                    {/foreach}
                </tr>
                </thead>
                <tbody>
                {foreach $configurations as $configName => $configValue}
                  <tr>
                    <td>
                      <span>{$configName}</span>
                      <span>&nbsp</span>
                        {if !$configValue.is_same}
                          <span class="badge-warning px-1">{l s='Config has different values' mod='shoppingfeed'}</span>
                        {else}
                          <span class="badge-success px-1">{l s='All config values are same' mod='shoppingfeed'}</span>
                        {/if}
                    </td>
                    <td>{$configValue.all_shop_value}</td>
                      {foreach $shopList as $shop}
                          {foreach $configValue.shops_value as $shopValue}
                              {if $shop.id_shop == $shopValue.id_shop}
                                <td>{$shopValue.value}</td>
                              {/if}
                          {/foreach}
                      {/foreach}
                  </tr>
                {/foreach}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
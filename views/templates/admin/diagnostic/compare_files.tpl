<div class="w-100 mb-3">
  <div class="row">
    <div class="col-sm-12">
      <div class="row justify-content-center">
        <div class="col-xl-12 pr-5 pl-5">
          <div class="card">
            <div class="card-header">
              <div class="col-sm-11">
                <span class="material-icons">dashboard</span>
                  {l s='Compare files integrity' mod='shoppingfeed'}
	          {assign var="tofix" value=true}
	          {foreach $files as $file => $status}
	            {if $status != 'ok'}
	              {assign var="tofix" value=true}
	          {/if}
	          {/foreach}
                  {if $tofix == true}
	            <span class="text-warning float-right">{l s='Module not up to date.' mod='shoppingfeed'}<span> <span class="material-icons text-warning float-right">close</span>
	          {else}
	            <span class="text-success float-right">{l s='Module up to date.' mod='shoppingfeed'}<span> <span class="material-icons text-success float-right">done</span>
	          {/if}
              </div>
            </div>
            <div class="form-wrapper justify-content-center col-xl-12 mt-3{if $tofix == false} d-none{/if}">
                <p>{l s='Modifications can cause conflicts. You\'ll found here the list of files difference between the original package and the module on teh server.' mod='shoppingfeed'}</p>
                <ul>
                    {foreach $files as $file => $status}
                    {if $status != 'ok'}
                    <li>
                        {$file}
                        {if $status == 'removed'}
                            {l s='File present in the original package but removed on your server.' mod='shoppingfeed'}
                        {elseif $status == 'modfied'}
                            {l s='File modified on your server.' mod='shoppingfeed'}
                        {elseif $status == 'added'}
                            {l s='File added on your server. Sould be removed.' mod='shoppingfeed'}
                        {else}
                            {$status}
                        {/if}
                    
                    </li>
                    {/if}
                    {/foreach}
                </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

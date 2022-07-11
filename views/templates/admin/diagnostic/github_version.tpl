<div class="w-100 mb-3">
  <div class="row">
    <div class="col-sm-12">
      {assign var="isUpToDate" value=true}
      {foreach $githubVersions as $index => $githubVersion}
        {if version_compare($githubVersion.name, $moduleVersion) > 0 and $githubVersion.prerelease == 0}
          {assign var="isUpToDate" value=false}
        {/if}
      {/foreach}
      <div class="row justify-content-center">
        <div class="col-xl-12 pr-5 pl-5">
          <div class="card">
            <div class="card-header d-flex justify-content-between">
              <div>
                <span class="material-icons">upgrade</span>
                {l s='Module version (github)' mod='shoppingfeed'}
              </div>
              {if $isUpToDate}
                <div class="badge-success px-2 mb-0">
                  {l s='Your module is up to date' mod='shoppingfeed'}
                </div>
              {else}
                <div class="badge-danger px-2 mb-0">
                  {l s='New version of your module found' mod='shoppingfeed'}
                </div>
              {/if}
            </div>
            <div class="form-wrapper justify-content-center col-xl-12 {if $isUpToDate}d-none{/if}">
              <div class="mt-2 alert alert-info">{l s='Keeping this module up to date allows better maintenance of your PrestaShop.' mod='shoppingfeed'}</div>

              {assign var="uptodate" value=true}
              {foreach name=githubVersions from=$githubVersions key=index item=githubVersion}
                {if version_compare($githubVersion.name, $moduleVersion) > 0 and $smarty.foreach.githubVersions.first}
                  {assign var="uptodate" value=false}
                  <div class="{if $index != (count($githubVersions)-1)}border-bottom{/if} mt-3 pb-3">
                    <div class="d-flex flex-column">
                      <div class="d-flex align-items-center">
                        <span>{{l s='Your version is: %a but lastest stable version is: %b.' mod='shoppingfeed'}|replace:['%a','%b']:[$moduleVersion, $githubVersion.name]}</span>
                      </div>
                    </div>
                    <div class="text-center">
                      <div class="font-weight-bold">
                        {l s='You can download the lastest release on GitHub' mod='shoppingfeed'}
                      </div>
                      <div>
                        {foreach $githubVersion.assets as $asset}
                          {if preg_match('/.zip$/', $asset.browser_download_url)}
                            <a href="{$asset.browser_download_url}"
                               class="btn btn-warning text-center inline">{$asset.name|escape:'html':'UTF-8'}</a>
                          {/if}
                        {/foreach}
                      </div>
                    </div>
                    <div>
                      <div class="font-weight-bold">
                        {l s='Lastest release note:' mod='shoppingfeed'}
                      </div>
                    </div>
                    <div>
                        {$githubVersion.body|nl2br}
                    </div>
                  </div>
                {elseif version_compare($githubVersion.name, $moduleVersion) == 0 && $uptodate == true}
                  <div class="d-flex align-items-center mt-3 pb-3">
                    <span class="material-icons text-success">close</span>
                    {l s='Nice, your addons looks up to date.' mod='shoppingfeed'}
                  </div>
                {/if}
              {/foreach}
              <div class="mt-3 mb-3" role="alert">You want to participate to the developers community or just be notified of new releases ? 
              Need more information, please visit our <a href="https://github.com/{$githubInfos|escape:'html':'UTF-8'}">GitHub public repository</a>.
              </div>

              {if empty($githubVersions)}
                <div class="alert alert-danger mt-3 mb-3" role="alert">
                  <p class="alert-text">{l s='Github repository not found or forbidden' mod='shoppingfeed'}</p>
                </div>
              {/if}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
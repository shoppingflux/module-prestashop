<div class="w-100 mb-3">
  <div class="row">
    <div class="col-sm-12">
      <div class="row justify-content-center">
        <div class="col-xl-12 pr-5 pl-5">
          <div class="card">
            <div class="card-header d-flex justify-content-between">
              <div>
                <span class="material-icons">description</span>
                  {l s='File integrity' mod='shoppingfeed'}
              </div>
                {if isset($missing) == false}
                  <div class="badge-secondary px-2 mb-0">{l s='Not available' mod='shoppingfeed'}</div>
                {elseif empty($created) || empty($missing) || empty($updated)}
                  <div class="badge-success px-2 mb-0">{l s='There are no missing or updated files' mod='shoppingfeed'}</div>
                {else}
                  <div class="badge-danger color-light px-2 mb-0">{l s='Missing or updated files found' mod='shoppingfeed'}</div>
                {/if}
            </div>
            <div class="form-wrapper justify-content-center col-xl-12 mt-3 d-none">
              <div class="mt-2 alert alert-info">{l s='Compare your shoppingfeed module files with the official package of the same release.' mod='shoppingfeed'}</div>
                {if !empty($missing)}
                  <p>{l s='These files are not found in your server, you should add it:' mod='shoppingfeed'}</p>
                  <ul class="list-unstyled">
                      {foreach $created as $missingFile}
                        <li><span class="material-icons">add</span> <abbr title="{l s='File added relative to the original package' mod='shoppingfeed'}">{$missingFile}</abbr></li>
                      {/foreach}
                      {foreach $missing as $missingFile}
                        <li><span class="material-icons">remove</span> <abbr title="{l s='File deleted relative to the original package' mod='shoppingfeed'}">{$missingFile}</abbr></li>
                      {/foreach}
                  </ul>
                {/if}
                {if !empty($updated)}
                  <p>{l s='These files were modified in module relative to the original package:' mod='shoppingfeed'}</p>
                  <div>
                      {foreach $updated as $updatedFile}
                        <details class="mb-2">
                          <summary>{$updatedFile.path}</summary>
                          <code>
                            <pre class="mt-2">{$updatedFile.diff|htmlentities}</pre>
                          </code>
                        </details>
                      {/foreach}
                  </div>
                {/if}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

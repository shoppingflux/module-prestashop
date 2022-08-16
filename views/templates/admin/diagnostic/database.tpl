<div class="w-100 mb-3">
  <div class="row">
    <div class="col-sm-12">
      <div class="row justify-content-center">
        <div class="col-xl-12 pr-5 pl-5">
          <div class="card">
            <div class="card-header d-flex justify-content-between">
              <div>
                <span class="material-icons">dataset</span>
                  {l s='Database integrity' mod='shoppingfeed'}
              </div>
                {if !$hasDatabaseErrors && empty($queries) && empty($optimizeQueries)}
                  <div class="badge-success px-2 mb-0">{l s='All tables are valid' mod='shoppingfeed'}</div>
                {else}
                  <div class="badge-danger px-2 mb-0">{l s='Some problems with module tables' mod='shoppingfeed'}</div>
                {/if}
            </div>
            <div class="form-wrapper justify-content-center col-xl-12 mt-3 {if !$hasDatabaseErrors && empty($queries) && empty($optimizeQueries)}d-none{/if}">
                <div class="mt-2 alert alert-info">{l s='Compare your shoppingfeed datablase schema with the official package of the same release.' mod='shoppingfeed'}</div>
                <div>
                  {foreach $tables as $tableTypeName => $tableTypes}
                      {foreach $tableTypes as $tableType}
                          {if !empty($tableType)}
                            <div>{l s='Table '} <strong>{$tableType.name}</strong></div>
                              {if !empty($tableType.errors)}
                                <ul>
                                    {foreach $tableType.errors as $tableTypeError}
                                      <li><span class="badge-danger px-2">{$tableTypeError}</span></li>
                                    {/foreach}
                                </ul>
                              {/if}
                              {if !empty($tableType.fields)}
                                <div class="table-wrapper">
                                  <table class="table border-bottom">
                                    <thead>
                                    <tr>
                                      <th>{l s='Column' mod='shoppingfeed'}</th>
                                      <th>{l s='Errors' mod='shoppingfeed'}</th>
                                    </tr>
                                    <tbody>
                                    {foreach $tableType.fields as $field}
                                      <tr>
                                        <td style="width: 20%">{$field.column}</td>
                                          {if !empty($field.errors)}
                                            <td>
                                              <ul>
                                                  {foreach $field.errors as $fieldError}
                                                    <li>{l s='Error: '} <span
                                                              class="badge-danger px-1">{$fieldError.text}</span></li>
                                                    <li>{l s='Actual: '} <span
                                                              class="badge-warning px-1">{$fieldError.actual}</span></li>
                                                    <li>{l s='Should be: '} <span
                                                              class="badge-success px-1">{$fieldError.fixed}</span></li>
                                                  {/foreach}
                                              </ul>
                                            </td>
                                          {else}
                                            <td>
                                              <span class="badge-success px-2">{l s='Field is valid' mod='shoppingfeed'}</span>
                                            </td>
                                          {/if}
                                      </tr>
                                    {/foreach}
                                    </tbody>
                                    </thead>
                                  </table>
                                </div>
                              {/if}
                          {/if}
                      {/foreach}
                  {/foreach}
              </div>
              {if !empty($queries) || !empty($optimizeQueries)}
                <div>
                  <h3>{l s='PS Database problems' mod='shoppingfeed'}</h3>
                    {function name='renderDbProblems'}
                        {foreach $queryItems as $queryName => $queryModels}
                          <div class="font-weight-bold text-warning mb-2">{$queryName}</div>
                            {foreach $queryModels as $queryModel}
                              <div class="border p-1 mb-1">
                                <div class="mb-1">
                                  <span class="font-weight-bold">{l s='Query: ' mod='shoppingfeed'}{$queryModel.query}</span>
                                </div>
                                <div class="mb-1">
                                  <span class="font-weight-bold">{l s='Fix query: ' mod='shoppingfeed'}{$queryModel.fix_query}</span>
                                </div>
                                {if !empty($queryModel.rows)}
                                  <div class="table-wrapper">
                                    <table class="table border-bottom mb-1">
                                      <thead>
                                        <tr>
                                          {foreach $queryModel.headers as $header}
                                            <th>{$header}</th>
                                          {/foreach}
                                        </tr>
                                        <tbody>
                                          {foreach $queryModel.rows as $row}
                                            <tr>
                                                {foreach $row as $column}
                                                  <td>{$column}</td>
                                                {/foreach}
                                            </tr>
                                          {/foreach}
                                        </tbody>
                                      </thead>
                                    </table>
                                  </div>
                                {/if}
                                {$queryModel.countRows}{l s=' résultats.' mod='shoppingfeed'}
                              </div>
                            {/foreach}
                        {/foreach}
                    {/function}
                    {if !empty($queries)}
                        {call name='renderDbProblems' queryItems=$queries}
                    {/if}
                    {if !empty($optimizeQueries)}
                        {call name='renderDbProblems' queryItems=$optimizeQueries}
                    {/if}
                </div>
              {/if}

            </div>
            <div class="card-footer {if !$hasDatabaseErrors && empty($queries) && empty($optimizeQueries)}d-none{/if}">
              <div class="justify-content-end {if !$hasDatabaseErrors && empty($queries) && empty($optimizeQueries)}d-none{else}d-flex{/if}">
                {if $hasDatabaseErrors}
                  <a href="{$actionsLink|cat:'&event=fixModuleTables'}" class="btn btn-lg btn-primary badge-info mx-1">
                      {l s='Fix module tables' mod='shoppingfeed'}
                  </a>
                {/if}
                {if !empty($queries)}
                  <a href="{$actionsLink|cat:'&event=fixTables'}" class="btn btn-lg btn-primary badge-info mx-1">
                      {l s='Fix Prestashop tables' mod='shoppingfeed'}
                  </a>
                {/if}
                {if !empty($optimizeQueries)}
                  <a href="{$actionsLink|cat:'&event=optimizeTables'}" class="btn btn-lg btn-primary badge-info mx-1">
                      {l s='Optimize tables' mod='shoppingfeed'}
                  </a>
                {/if}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
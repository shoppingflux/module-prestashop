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
                <div class="mt-2 alert alert-info">{l s='Compare your %s database schema with the official package of the same release.' sprintf=$module_name mod='shoppingfeed'}</div>
                <div>
                  {foreach $tables as $tableTypeName => $tableTypes}
                      {foreach $tableTypes as $tableType}
                          {if !empty($tableType)}
                            <div>{l s='Table'  mod='shoppingfeed'} <strong>{$tableType.name|escape:'html':'UTF-8'}</strong></div>
                              {if !empty($tableType.errors)}
                                <ul>
                                    {foreach $tableType.errors as $tableTypeError}
                                      <li><span class="badge-danger px-2">{$tableTypeError|escape:'html':'UTF-8'}</span></li>
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
                                        <td style="width: 20%">{$field.column|escape:'html':'UTF-8'}</td>
                                          {if !empty($field.errors)}
                                            <td>
                                              <ul>
                                                  {foreach $field.errors as $fieldError}
                                                    <li>{l s='Error:' mod='shoppingfeed'} <span
                                                              class="badge-danger px-1">{$fieldError.text|escape:'html':'UTF-8'}</span></li>
                                                    <li>{l s='Actual:' mod='shoppingfeed'} <span
                                                              class="badge-warning px-1">{$fieldError.actual|escape:'html':'UTF-8'}</span></li>
                                                    <li>{l s='Should be:' mod='shoppingfeed'} <span
                                                              class="badge-success px-1">{$fieldError.fixed|escape:'html':'UTF-8'}</span></li>
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
                          <div class="font-weight-bold text-warning mb-2">{$queryName|escape:'html':'UTF-8'}</div>
                            {foreach $queryModels as $queryModel}
                              <div class="border p-1 mb-1">
                                <div class="mb-1">
                                  <span class="font-weight-bold">{l s='Query: ' mod='shoppingfeed'}{$queryModel.query|escape:'html':'UTF-8'}</span>
                                </div>
                                <div class="mb-1">
                                  <span class="font-weight-bold">{l s='Fix query: ' mod='shoppingfeed'}{$queryModel.fix_query|escape:'html':'UTF-8'}</span>
                                </div>
                                {if !empty($queryModel.rows)}
                                  <div class="table-wrapper">
                                    <table class="table border-bottom mb-1">
                                      <thead>
                                        <tr>
                                          {foreach $queryModel.headers as $header}
                                            <th>{$header|escape:'html':'UTF-8'}</th>
                                          {/foreach}
                                        </tr>
                                        <tbody>
                                          {foreach $queryModel.rows as $row}
                                            <tr>
                                                {foreach $row as $column}
                                                  <td>{$column|escape:'html'|truncate:150}</td>
                                                {/foreach}
                                            </tr>
                                          {/foreach}
                                        </tbody>
                                      </thead>
                                    </table>
                                  </div>
                                {/if}
                                  {if empty($queryModel.countRows) === true}
                                    {$queryModel.rows|count}{l s=' résultats.' mod='shoppingfeed'}
                                  {else}
                                    {$queryModel.countRows|escape:'html':'UTF-8'}{l s=' résultats.' mod='shoppingfeed'}
                                  {/if}
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
                  <a href="{$actionsLink|cat:'&event=fixModuleTables'|escape:'html':'UTF-8'}" class="btn btn-lg btn-primary badge-info mx-1">
                      {l s='Fix module tables' mod='shoppingfeed'}
                  </a>
                {/if}
                {if !empty($queries)}
                  <a href="{$actionsLink|cat:'&event=fixTables'|escape:'html':'UTF-8'}" class="btn btn-lg btn-primary badge-info mx-1">
                      {l s='Fix Prestashop tables' mod='shoppingfeed'}
                  </a>
                {/if}
                {if !empty($optimizeQueries)}
                  <a href="{$actionsLink|cat:'&event=optimizeTables'|escape:'html':'UTF-8'}" class="btn btn-lg btn-primary badge-info mx-1">
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

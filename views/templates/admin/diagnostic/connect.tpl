<div class="w-100 mb-3">
  <div class="row">
    <div class="col-sm-12">
      <div class="row justify-content-center">
        <div class="col-xl-12 pr-5 pl-5">
          <div class="card">
            <div class="card-header d-flex justify-content-between">
              <div>
                <span class="material-icons">wifi</span>
                  {l s='Connection' mod='shoppingfeed'}
              </div>
            </div>
            <form action="{$actionsLink|cat:'&event=saveConnectStub'}"
                  class="d-flex flex-column mb-0"
                  method="post">
              <div class="form-wrapper justify-content-center col-xl-12 my-3">

                <div class="form-group row">
                  <label class="form-control-label col-lg-4 justify-content-end align-items-center">
                      {l s='SSO available' mod='__mod__'}
                  </label>
                  <div class="col-lg-4 align-items-center">
                    <span class="ps-switch ps-switch-lg">
                    <input
                            type="radio"
                            name="sso"
                            value="0"
                            {if !$connection.sso}checked{/if}
                    />
                    <label class="ml-4 pl-4">{l s='Off' mod='shoppingfeed'}</label>
                    <input
                            type="radio"
                            name="sso"
                            value="1"
                            {if $connection.sso}checked{/if}
                    />
                    <label class="ml-4 pl-4">{l s='On' mod='shoppingfeed'}</label>
                    <span class="slide-button"></span>
                  </span>
                  </div>
                </div>

                <div class="form-group row">
                  <label class="form-control-label col-lg-4 justify-content-end align-items-center">{l s='Employee' mod='shoppingfeed'}</label>
                  <div class="col-lg-4 align-items-center">
                    <select class="form-control"
                            name="employee"
                            data-toggle="select2"
                            data-minimumresultsforsearch="1"
                            aria-hidden="true">
                        {foreach $connection.employees as $employee}
                          <option value="{$employee.id}" {if $employee.selected}selected{/if}>{$employee.name}</option>
                        {/foreach}
                    </select>
                  </div>
                </div>
                <div class="form-group row">
                  <label class="form-control-label col-lg-4 justify-content-end align-items-center">{l s='Restricted IPs' mod='shoppingfeed'}</label>
                  <div class="col-lg-4 align-items-center">
                    <input type="text" class="form-control" name="ips" value="{$connection.ips}"/>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <div class="d-flex justify-content-end">
                  <button type="submit"
                          class="btn btn-default">{l s='Save and share' mod='shoppingfeed'}</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
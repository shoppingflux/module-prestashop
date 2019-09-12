{extends file="helpers/form/form.tpl"}

{block name="legend" append}
<div id="status_generale_settings_div" class="form-wrapper">
    <div class="form-group shoppingfeed_double-list-group">
        <label class='control-label col-lg-3'>
            {l s='Shipped orders synchronization' mod='shoppingfeed'}
        </label>
        
        <div class="col-lg-9">
            <div class="row">
                <div class="col-lg-6 shoppingfeed_double-list-left-container">
                    <span class="bold">{l s='Unselected order status' mod='shoppingfeed'}</span>
                    <select id="status_shipped_order_add" class="input-large" multiple>
                        {foreach from=$order_shipped.unselected item='status'}
                            <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                        {/foreach}
                    </select>
                    <a id="status_shipped_order_add_btn" class="btn btn-default btn-block clearfix" >{l s='Add' d='Admin.Actions'} <i class="icon-arrow-right"></i></a>
                </div>

                <div class="col-lg-6 shoppingfeed_double-list-right-container">
                    <span class="bold">{l s='Selected status'  mod='shoppingfeed'}</span>
                    <select id="status_shipped_order_remove" class="input-large" name="status_shipped_order[]  " multiple>
                        {foreach from=$order_shipped.selected item='status'}
                            <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                        {/foreach}
                    </select>
                    <a id="status_shipped_order_remove_btn" class="btn btn-default btn-block clearfix"><i class="icon-arrow-left"></i> {l s='Remove' d='Admin.Actions'} </a>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class='control-label col-lg-3'>
            <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="{l s='In some cases, the tracking number can be sent to your shop after the order status update. For being sure and always sending the tracking numbers to the marketplaces you can set a shift time (in minutes). By default, the sending of the tracking number will be delayed by 5 minutes.' mod='shoppingfeed'}">
                {l s='Time shift for tracking numbers synchronization' mod='shoppingfeed'}
            </span>
        </label>
        <div class="col-lg-9">
            <div class="col-lg-6 input-group">
                    <input type="text" class="form-control" name="tracking_timeshift" value="{$time_shift}">
                    <span class="input-group-addon">
                    {l s='minutes' mod='shoppingfeed'}
                    </span>
            </div>
        </div>
    </div>

    <div class="form-group shoppingfeed_double-list-group">
        <label class='control-label col-lg-3'>
            {l s='Cancelled orders synchronization' mod='shoppingfeed'}</span>
        </label>
        
        <div class="col-lg-9">
            <div class="row">
                <div class="col-lg-6 shoppingfeed_double-list-left-container">
                    <span class="bold">
                        {l s='Unselected order status' mod='shoppingfeed'}
                    </span>
                    <select id="status_cancelled_order_add" class="input-large" multiple>
                        {foreach from=$order_cancelled.unselected item='status'}
                            <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                        {/foreach}
                    </select>
                    <a id="status_cancelled_order_add_btn" class="btn btn-default btn-block clearfix" >{l s='Add' d='Admin.Actions'} <i class="icon-arrow-right"></i></a>
                </div>
                <div class="col-lg-6 shoppingfeed_double-list-right-container">
                    <span class="bold">{l s='Selected status'  mod='shoppingfeed'}</span>
                    <select id="status_cancelled_order_remove" class="input-large" name="status_cancelled_order[]" multiple>
                        {foreach from=$order_cancelled.selected item='status'}
                            <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                        {/foreach}
                    </select>
                    <a id="status_cancelled_order_remove_btn" class="btn btn-default btn-block clearfix"><i class="icon-arrow-left"></i> {l s='Remove' d='Admin.Actions'} </a>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group shoppingfeed_double-list-group">
        <label class='control-label col-lg-3'>
            {l s='Refunded orders synchronization' mod='shoppingfeed'}</span>
        </label>
        
        <div class="col-lg-9">
            <div class="row">
                <div class="col-lg-6 shoppingfeed_double-list-left-container">
                    <span class="bold">
                        {l s='Unselected order status' mod='shoppingfeed'}
                    </span>
                        <select id="status_refunded_order_add" class="input-large" multiple>
                            {foreach from=$order_refunded.unselected item='status'}
                                <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                            {/foreach}
                        </select>
                        <a id="status_refunded_order_add_btn" class="btn btn-default btn-block clearfix" >{l s='Add' d='Admin.Actions'} <i class="icon-arrow-right"></i></a>
                </div>
                <div class="col-lg-6 shoppingfeed_double-list-right-container">
                    <span class="bold">
                        {l s='Selected status'  mod='shoppingfeed'}
                    </span>
                    <select id="status_refunded_order_remove" class="input-large" name="status_refunded_order[]" multiple>
                        {foreach from=$order_refunded.selected item='status'}
                            <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                        {/foreach}
                    </select>
                    <a id="status_refunded_order_remove_btn" class="btn btn-default btn-block clearfix"><i class="icon-arrow-left"></i> {l s='Remove' d='Admin.Actions'} </a>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        {l s='You should set the frequency of synchronization via a' mod='shoppingfeed'} <a href="{$cron_link}">{l s='Cron job' mod='shoppingfeed'}</a> {l s='for updating your orders status' mod='shoppingfeed'}
    </div>

    <div class="alert alert-warning">
        {l s='The Max order update parameter is reserved for experts (100 by default). You can configure the number of orders to be processed each time the cron job is called. The more you increase this number, the greater the number of database queries. The value of this parameter is to be calibrated according to the capacities of your MySQL server and your stock rotation rate to process the queue in the time that suits you.' mod='shoppingfeed'}
    </div>
    <br />


    <div class="form-group">
        <label class='control-label col-lg-3'>
            {l s='Max. order update per request' mod='shoppingfeed'}
        </label>
        <div class="col-lg-9">
            <input type="text" class="number_require" name="max_order_update" value="{$max_orders}">
        </div>
    </div>
</div>
{/block}
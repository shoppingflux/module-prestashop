{extends file="helpers/form/form.tpl"}

{block name="legend" append}
<div id="status_generale_settings_div">
    <br />
    <span class="bold">{l s="Shipped orders synchronization" mod="shoppingfeed"}</span>
    <table class="table">
        <tr>
            <td>
                <p>{l s='Unselected order status' mod='shoppingfeed'}</p>
                <select id="status_shipped_order_add" class="input-large" multiple>
                    {foreach from=$order_shipped.unselected item='status'}
                        <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                    {/foreach}
                </select>
                <a id="status_shipped_order_add_btn" class="btn btn-default btn-block clearfix" >{l s='Add' d='Admin.Actions'} <i class="icon-arrow-right"></i></a>
            </td>
            <td>
                <p>{l s='Selected status'  mod='shoppingfeed'}</p>
                <select id="status_shipped_order_remove" class="input-large" name="status_shipped_order[]  " multiple>
                    {foreach from=$order_shipped.selected item='status'}
                        <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                    {/foreach}
                </select>
                <a id="status_shipped_order_remove_btn" class="btn btn-default btn-block clearfix"><i class="icon-arrow-left"></i> {l s='Remove' d='Admin.Actions'} </a>
            </td>
        </tr>
    </table>
    <br />

    <span class="bold">{l s="Time shit for tracking numbers synchronization" mod="shoppingfeed"}</span>
    <input type="text" name="tracking_timeshit" value="{$time_shit}">
    <span class="bold">{l s="minutes" mod="shoppingfeed"}</span>
    <br /><br />

    <span class="bold">{l s="Cancelled orders synchronization" mod="shoppingfeed"}</span>
    <table class="table">
        <tr>
            <td>
                <p>{l s='Unselected order status' mod='shoppingfeed'}</p>
                <select id="status_cancelled_order_add" class="input-large" multiple>
                    {foreach from=$order_cancelled.unselected item='status'}
                        <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                    {/foreach}
                </select>
                <a id="status_cancelled_order_add_btn" class="btn btn-default btn-block clearfix" >{l s='Add' d='Admin.Actions'} <i class="icon-arrow-right"></i></a>
            </td>
            <td>
                <p>{l s='Selected status'  mod='shoppingfeed'}</p>
                <select id="status_cancelled_order_remove" class="input-large" name="status_cancelled_order[]" multiple>
                    {foreach from=$order_cancelled.selected item='status'}
                        <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                    {/foreach}
                </select>
                <a id="status_cancelled_order_remove_btn" class="btn btn-default btn-block clearfix"><i class="icon-arrow-left"></i> {l s='Remove' d='Admin.Actions'} </a>
            </td>
        </tr>
    </table>
    <br />

    <span class="bold">{l s="Refunded orders synchronization" mod="shoppingfeed"}</span>
    <table class="table">
        <tr>
            <td>
                <p>{l s='Unselected order status' mod='shoppingfeed'}</p>
                <select id="status_refunded_order_add" class="input-large" multiple>
                    {foreach from=$order_refunded.unselected item='status'}
                        <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                    {/foreach}
                </select>
                <a id="status_refunded_order_add_btn" class="btn btn-default btn-block clearfix" >{l s='Add' d='Admin.Actions'} <i class="icon-arrow-right"></i></a>
            </td>
            <td>
                <p>{l s='Selected status'  mod='shoppingfeed'}</p>
                <select id="status_refunded_order_remove" class="input-large" name="status_refunded_order[]" multiple>
                    {foreach from=$order_refunded.selected item='status'}
                        <option value="{$status.id_order_state|intval}">&nbsp;{$status.name|escape}</option>
                    {/foreach}
                </select>
                <a id="status_refunded_order_remove_btn" class="btn btn-default btn-block clearfix"><i class="icon-arrow-left"></i> {l s='Remove' d='Admin.Actions'} </a>
            </td>
        </tr>
    </table>
    <br />

    <div class="alert alert-info">
        {l s="You should set the synchronization via a" mod="shoppingfeed"} <a href="#">{l s="Cron job" mod="shoppingfeed"}</a> {l s="for updating your orders status" mod="shoppingfeed"}
    </div>

    <div class="alert alert-warning">
        {l s="The Max product update parameter is reserved for experts (100 by default). You can configure the number of products to be processed each time the cron job is called. The more you increase this number, the greater the number of database queries. The value of this parameter is to be calibrated according to the capacities of your MySQL server and your stock rotation rate to process the queue in the time that suits you." mod="shoppingfeed"}
    </div>
    <br />

    <span class="bold">{l s="Max. order update per request" mod="shoppingfeed"}</span>
    <input type="text" name="max_order_update" value="{$max_orders}">
</div>
{/block}
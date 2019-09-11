{foreach from=$failedTaskOrders item="taskOrder"}
    <ul>
        <li>Order {$taskOrder->id}</li>
    </ul>
{/foreach}
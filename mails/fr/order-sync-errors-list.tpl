{foreach from=$failedTaskOrdersData item="taskOrderData"}
    <tr>
        <td style="border:1px solid #D6D4D4;text-align:center;color:#777;padding:7px 0">
            {$taskOrderData.reference}
        </td>
        <td style="border:1px solid #D6D4D4;text-align:center;color:#777;padding:7px 0">
            {$taskOrderData.status}
        </td>
    </tr>
{/foreach}
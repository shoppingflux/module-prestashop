{l s='Customer ID' mod='shoppingfeed'} : {$zalando_customer} <br />
{l s='Products' mod='shoppingfeed'} :  <br />
{foreach from=$zalando_products item=zalando_product}
    - {$zalando_product} <br />
{/foreach}

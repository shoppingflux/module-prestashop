{if empty($fileContent) && empty($dbContent)}
    {l s='Log unavailable' mod='shoppingfeed'}
{elseif !empty($fileContent)}
  <pre>{$fileContent}</pre>
{elseif !empty($dbContent)}
  <table class="table border">
    <thead>
    {foreach $dbContent.headers as $header}
      <th>{$header}</th>
    {/foreach}
    </thead>
    <tbody>
    {foreach $dbContent.content as $line}
        <tr>
          {foreach $line as $name => $value}
            <td>{$value}</td>
          {/foreach}
        </tr>
    {/foreach}
    </tbody>
  </table>
{/if}
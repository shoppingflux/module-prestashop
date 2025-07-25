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
{if empty($fileContent) && empty($dbContent)}
    {l s='Log unavailable' mod='shoppingfeed'}
{elseif !empty($fileContent)}
  <pre>{$fileContent|escape:'html':'UTF-8'}</pre>
{elseif !empty($dbContent)}
  <table class="table border">
    <thead>
    {foreach $dbContent.headers as $header}
      <th>{$header|escape:'html':'UTF-8'}</th>
    {/foreach}
    </thead>
    <tbody>
    {foreach $dbContent.content as $line}
        <tr>
          {foreach $line as $name => $value}
            <td>{$value|escape:'html':'UTF-8'}</td>
          {/foreach}
        </tr>
    {/foreach}
    </tbody>
  </table>
{/if}